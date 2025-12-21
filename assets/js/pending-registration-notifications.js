// Pending Registration Notifications
// - Plays a short ringtone when new pending registrations are detected
// - Uses Howler.js when available, falls back to Web Audio API
// - Shows a toast (using global showToast) and a Desktop Notification when permitted

(function(window){
	const MODULE = {};

	MODULE.userInteracted = false; // becomes true after any user gesture
	MODULE._enabled = false;
	MODULE._howl = null;
	MODULE._audioCtx = null;
	MODULE._gain = null;
	MODULE._useOscillator = false;
	MODULE._audioValidationFailed = false; // avoid repeated fetch/decode attempts
	MODULE._audioBlob = null; // validated audio blob for deferred Howl creation
	MODULE._audioValidated = false;
	MODULE._howlUrl = null;

	// Generate a short WAV beep blob (fallback when notification.mp3 is missing)
	function _generateBeepWavBlob(durationSec = 0.15, frequency = 880, sampleRate = 44100) {
		const samples = Math.floor(sampleRate * durationSec);
		const buffer = new ArrayBuffer(44 + samples * 2);
		const view = new DataView(buffer);

		function writeString(view, offset, string) {
			for (let i = 0; i < string.length; i++) {
				view.setUint8(offset + i, string.charCodeAt(i));
			}
		}

		// RIFF identifier
		writeString(view, 0, 'RIFF');
		view.setUint32(4, 36 + samples * 2, true);
		writeString(view, 8, 'WAVE');
		writeString(view, 12, 'fmt ');
		view.setUint32(16, 16, true); // PCM chunk size
		view.setUint16(20, 1, true); // PCM format
		view.setUint16(22, 1, true); // channels
		view.setUint32(24, sampleRate, true); // sample rate
		view.setUint32(28, sampleRate * 2, true); // byte rate (sampleRate * blockAlign)
		view.setUint16(32, 2, true); // block align
		view.setUint16(34, 16, true); // bits per sample
		writeString(view, 36, 'data');
		view.setUint32(40, samples * 2, true);

		// fill samples (16-bit PCM)
		for (let i = 0; i < samples; i++) {
			const t = i / sampleRate;
			// simple envelope to avoid clicks
			const env = Math.min(1, i / (sampleRate * 0.01));
			const sample = Math.max(-1, Math.min(1, Math.sin(2 * Math.PI * frequency * t) * env));
			view.setInt16(44 + i * 2, sample * 0x7FFF, true);
		}

		return new Blob([view], { type: 'audio/wav' });
	}

	// small beep sequence using WebAudio (fallback)
	function _playOscillator(times){
		try {
			if (!MODULE._audioCtx) {
				MODULE._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
				MODULE._gain = MODULE._audioCtx.createGain();
				MODULE._gain.connect(MODULE._audioCtx.destination);
				MODULE._gain.gain.value = 0.2;
			}

			const now = MODULE._audioCtx.currentTime;
			for (let i = 0; i < times; i++){
				const start = now + i * 0.5;
				const osc = MODULE._audioCtx.createOscillator();
				osc.type = 'sine';
				osc.frequency.setValueAtTime(880, MODULE._audioCtx.currentTime);
				osc.connect(MODULE._gain);
				osc.start(start);
				osc.stop(start + 0.18);
			}
		} catch (e) {
			console.warn('AudioContext error', e);
		}
	}

	function _playHowler(times){
		// If Howl instance isn't created yet, try to create it now — but only after user interaction
		if (!MODULE._howl) {
			if (MODULE._audioBlob && MODULE.userInteracted && window.Howl) {
				try {
					// create a blob URL for Howler now
					try { MODULE._howlUrl && URL.revokeObjectURL(MODULE._howlUrl); } catch(e){}
					const url = URL.createObjectURL(MODULE._audioBlob);
					MODULE._howlUrl = url;
					MODULE._howl = new Howl({
						src: [url],
						volume: 0.9,
						pool: 3,
						onplayerror: function(id, err) { console.warn('Howl onplayerror', id, err); MODULE._howl = null; },
						onloaderror: function(id, err) { console.warn('Howl onloaderror', id, err); MODULE._howl = null; }
					});
				} catch (e) {
					MODULE._howl = null;
				}
			} else {
				// Can't create Howl yet — fall back to oscillator
				return _playOscillator(times);
			}
		}
		try {
			for (let i=0;i<times;i++){
				// stagger plays to avoid overlap
				setTimeout(()=> MODULE._howl && MODULE._howl.play(), i * 550);
			}
		} catch (e){
			console.warn('Howl play error', e);
			_playOscillator(times);
		}
	}

	MODULE.enable = function(){
		if (MODULE._enabled) return;
		MODULE._enabled = true;
		console.debug && console.debug('PendingRegistrationNotifications.enable() called');

		// create Howl if available and audio asset exists (check via HEAD)
		try{
			if (window.Howl) {
				fetch('assets/audio/notification.mp3', { method: 'HEAD' }).then(resp => {
					const contentType = resp.headers.get('content-type') || '';
					if (resp.ok && contentType.startsWith('audio')) {
						// Fetch the audio as an ArrayBuffer and try to decode it using WebAudio first.
						// This prevents Howler from attempting to decode corrupted/unsupported files
						// which would surface "Decoding audio data failed" errors.
						if (MODULE._audioValidationFailed) {
							MODULE._howl = null;
							return;
						}
						// Use an HTMLAudioElement probe to detect playability without invoking WebAudio decode errors.
						// Create a blob URL and attach to an Audio element, listen for 'canplaythrough' or 'error'.
						if (MODULE._audioValidationFailed) {
							MODULE._howl = null;
						} else {
							fetch('assets/audio/notification.mp3').then(r => r.blob()).then(blob => {
								try {
									// Detect placeholder text file (repo contains a placeholder message) and fall back to oscillator
									if (blob.type && blob.type.indexOf('text') === 0) {
										console.debug && console.debug('notification.mp3 appears to be a placeholder text file; using embedded beep fallback');
										try {
											MODULE._audioBlob = _generateBeepWavBlob(0.15, 880);
											MODULE._audioValidated = true;
											MODULE._useOscillator = false;
										} catch (e) {
											MODULE._audioBlob = null;
											MODULE._audioValidated = false;
											MODULE._useOscillator = true;
										}
										return;
									}
									const url = URL.createObjectURL(blob);
									const probe = new Audio();
									let settled = false;
									probe.preload = 'auto';
									probe.src = url;
									probe.crossOrigin = 'anonymous';

									const cleanup = () => {
										try { probe.pause(); } catch(e){}
										probe.src = '';
										// Do NOT revoke the object URL here — we defer Howl creation until after a user gesture.
									};

									const onSuccess = () => {
										if (settled) return; settled = true;
										cleanup();
																					console.debug && console.debug('PendingRegistrationNotifications: audio probe success');
										// Store the validated blob for deferred Howl creation (do not instantiate Howl now)
										try {
											MODULE._audioBlob = blob;
											MODULE._audioValidated = true;
																					console.debug && console.debug('PendingRegistrationNotifications: audio blob validated');
											MODULE._useOscillator = false;
										} catch (we) {
											MODULE._audioBlob = null;
											MODULE._audioValidated = false;
											MODULE._useOscillator = true;
										}
									};

									const onError = (ev) => {
										if (settled) return; settled = true;
										cleanup();
										// Probe failed — switch to oscillator fallback quietly
										console.debug('Audio probe failed (probe error)');
										MODULE._audioBlob = null;
										MODULE._useOscillator = true;
										MODULE._audioValidated = false;
																					console.debug && console.debug('PendingRegistrationNotifications: audio probe failed, using oscillator');
									};

									probe.addEventListener('canplaythrough', onSuccess, { once: true });
									probe.addEventListener('loadedmetadata', onSuccess, { once: true });
									probe.addEventListener('error', onError, { once: true });

									// Timeout: if neither event fires in 3s, treat as failed
									setTimeout(() => {
										if (settled) return;
										settled = true;
										cleanup();
										// Timeout — treat as non-fatal and fall back silently
										console.debug('Audio probe timeout');
										MODULE._audioBlob = null;
										MODULE._useOscillator = true;
										MODULE._audioValidated = false;
									}, 3000);
								} catch (e) {
									console.warn('Audio probe failed', e);
									MODULE._howl = null;
									MODULE._useOscillator = true;
									MODULE._audioValidationFailed = true;
								}
							}).catch(errFetch => {
								console.debug('Failed to fetch audio for validation');
								MODULE._howl = null;
								MODULE._useOscillator = true;
								MODULE._audioValidationFailed = true;
							});
							console.debug && console.debug('PendingRegistrationNotifications: enable() started audio HEAD check');
						}
					} else {
						MODULE._howl = null;
						MODULE._useOscillator = true;
						MODULE._audioValidationFailed = true;
					}
				}).catch(err => {
					MODULE._howl = null;
				});
			}
		} catch (e){
			MODULE._howl = null;
		}

		// listen for first user interaction to mark permission for autoplay and resume audio context
		function onFirstInteraction(){
			MODULE.userInteracted = true;
			window.removeEventListener('click', onFirstInteraction);
			window.removeEventListener('keydown', onFirstInteraction);
			// resume audio context if exists
			try { if (MODULE._audioCtx && MODULE._audioCtx.state === 'suspended') MODULE._audioCtx.resume(); } catch(e){}
			// if Howl exists, unlock (Howler usually auto-unlocks on gesture)
		}

		window.addEventListener('click', onFirstInteraction);
		window.addEventListener('keydown', onFirstInteraction);
	};

	// Attempt to unlock/resume audio without an explicit user click.
	// This is a best-effort; browsers may still block autoplay until a real user gesture.
	// Attempt to unlock/resume audio.
	// `forceGesture` should be true when called directly from a user gesture.
	MODULE.tryUnlock = async function(forceGesture = false){
		try {
			console.debug && console.debug('PendingRegistrationNotifications.tryUnlock called, forceGesture=', forceGesture, 'userInteracted=', MODULE.userInteracted);
			// If we already created an AudioContext, try to resume it.
			if (MODULE._audioCtx) {
				if (MODULE._audioCtx.state === 'suspended') {
					await MODULE._audioCtx.resume();
				}
			} else {
				// Allow creating an AudioContext either when called from a user gesture
				// (forceGesture=true) or when we already recorded a prior gesture (MODULE.userInteracted).
				if (!forceGesture && !MODULE.userInteracted) {
					// Avoid creating AudioContext now — browsers may reject it and log the error.
					return false;
				}
				try {
					MODULE._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
					MODULE._gain = MODULE._audioCtx.createGain();
					MODULE._gain.connect(MODULE._audioCtx.destination);
					MODULE._gain.gain.value = 0.0001; // nearly silent for the unlock attempt
					if (MODULE._audioCtx.state === 'suspended') {
						await MODULE._audioCtx.resume();
					}
				} catch (createErr) {
					// Failed to create/resume audio context — give up silently
					console.debug && console.debug('tryUnlock: could not create AudioContext', createErr);
					return false;
				}
			}

			// Try a very short oscillator burst at near-zero gain to trigger playback permission
			const osc = MODULE._audioCtx.createOscillator();
			osc.type = 'sine';
			osc.frequency.setValueAtTime(440, MODULE._audioCtx.currentTime);
			osc.connect(MODULE._gain);
			osc.start(MODULE._audioCtx.currentTime);
			osc.stop(MODULE._audioCtx.currentTime + 0.02);

			// wait a short time for the engine to process
			await new Promise(r => setTimeout(r, 60));

			// If we reached here without exceptions, consider audio unlocked
			MODULE.userInteracted = true;
			console.debug && console.debug('PendingRegistrationNotifications.tryUnlock succeeded, audio context resumed/created');
			try { MODULE._gain.gain.value = 0.2; } catch (e){}
			return true;
		} catch (e) {
			// Don't spam console with autoplay policy errors; return false silently
			console.debug && console.debug('PendingRegistrationNotifications.tryUnlock failed', e);
			return false;
		}
	};

	// Register multiple gesture/visibility/focus events to repeatedly attempt unlock.
	MODULE.initAutoUnlock = function(){
		// initAutoUnlock intentionally disabled — audio unlock should only be attempted from login gesture now.
		// Keep function defined to avoid errors in other scripts.
		return;
	};

	// Unobtrusive enable prompt: small pill with a button that forces a user-gesture unlock
	MODULE.showEnablePrompt = function(){
		// Disabled — enabling audio should only be performed during login gesture.
		return;
	};

	MODULE.playForNewRegistrations = async function(newCount, oldCount){
		// Suppress sound if navigation just occurred to avoid playing on nav clicks
		try {
			if (window.NotificationNavClickSuppressed) {
				console.debug('Notification suppressed due to nav click');
				return;
			}
		} catch (e) {}
		const delta = Math.max(1, (newCount - oldCount));
		const times = Math.min(delta, 5); // max 5 rings

		console.debug && console.debug('PendingRegistrationNotifications.playForNewRegistrations()', { newCount, oldCount, times });
		// Play sound (Howler will be created lazily after a user gesture if available)
		_playHowler(times);

		// Desktop notification with icon fallback (avoid 404)
		try {
			if ('Notification' in window && Notification.permission === 'granted') {
				let iconUrl = 'assets/icons/notification.png';
				try {
					const head = await fetch(iconUrl, { method: 'HEAD' });
					if (!head.ok) throw new Error('icon not found');
				} catch (iconErr) {
					// fallback to an inline SVG data URL (bell emoji)
					const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='128' height='128'><rect width='100%' height='100%' fill='transparent'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' font-size='72'>🔔</text></svg>`;
					iconUrl = 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
				}
				const notif = new Notification('New Pending Registration', {
					body: `${delta} new registration${delta>1? 's':''} awaiting review.`,
					icon: iconUrl
				});
				setTimeout(() => notif.close(), 8000);
			}
		} catch (e) {
			console.warn('Notification error', e);
		}

		// Fallback toast using global showToast if available
		if (typeof showToast === 'function') {
			showToast('success', 'New Registration', `${delta} new registration${delta>1? 's':''} awaiting review.`);
		} else {
			console.log('New registration(s):', delta);
		}
	};

	// Expose module
	window.PendingRegistrationNotifications = MODULE;

	// Request permission proactively (non-blocking)
	if ('Notification' in window && Notification.permission === 'default') {
		try { Notification.requestPermission(); } catch(e){}
	}

	// If login previously set a flag indicating audio was enabled during the login gesture,
	// attempt a best-effort unlock/resume now so subsequent pages can play sounds without
	// another user gesture. This will succeed only on browsers that treat the prior
	// gesture as granting autoplay permission across navigations.
	try {
		// Prefer a server-provided JS flag `window.PENDING_AUDIO_UNLOCKED`.
		// Do NOT rely on localStorage because users may clear site data.
		const flag = (typeof window !== 'undefined' && window.PENDING_AUDIO_UNLOCKED) ? true : false;
		if (flag) {
			// Treat login-provided flag as a prior user interaction.
			try { MODULE.userInteracted = true; } catch(e){}
			try { MODULE.enable(); } catch(e){}
			MODULE.tryUnlock(false).then(ok => {
				if (ok) console.debug && console.debug('PendingRegistrationNotifications: audio unlocked at startup');
			}).catch(()=>{});
		}
	} catch (e) { /* ignore */ }

	// If login set the transient flag but the browser blocked autoplay on first load,
	// try again when the page becomes visible or window regains focus.
	try {
		const flag = (typeof window !== 'undefined' && window.PENDING_AUDIO_UNLOCKED) ? true : false;
		if (flag) {
			document.addEventListener('visibilitychange', function(){
				if (document.visibilityState === 'visible') {
					try { MODULE.tryUnlock(false).catch(()=>{}); } catch(e){}
				}
			});
			window.addEventListener('focus', function(){ try { MODULE.tryUnlock(false).catch(()=>{}); } catch(e){} });
		}
	} catch (e) { /* ignore */ }

})(window);

