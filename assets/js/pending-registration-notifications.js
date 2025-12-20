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
		if (!MODULE._howl) return _playOscillator(times);
		try {
			for (let i=0;i<times;i++){
				// stagger plays to avoid overlap
				setTimeout(()=> MODULE._howl.play(), i * 550);
			}
		} catch (e){
			console.warn('Howl play error', e);
			_playOscillator(times);
		}
	}

	MODULE.enable = function(){
		if (MODULE._enabled) return;
		MODULE._enabled = true;

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
									const url = URL.createObjectURL(blob);
									const probe = new Audio();
									let settled = false;
									probe.preload = 'auto';
									probe.src = url;
									probe.crossOrigin = 'anonymous';

									const cleanup = () => {
										try { probe.pause(); } catch(e){}
										probe.src = '';
										try { URL.revokeObjectURL(url); } catch(e){}
									};

									const onSuccess = () => {
										if (settled) return; settled = true;
										cleanup();
										try {
											MODULE._howl = new Howl({
												src: [url],
												volume: 0.9,
												pool: 3,
												onplayerror: function(id, err) { console.warn('Howl onplayerror', id, err); MODULE._howl = null; },
												onloaderror: function(id, err) { console.warn('Howl onloaderror', id, err); MODULE._howl = null; }
											});
										} catch (we) {
											MODULE._howl = null;
										}
									};

									const onError = (ev) => {
										if (settled) return; settled = true;
										cleanup();
										// Probe failed — switch to oscillator fallback quietly
										console.debug('Audio probe failed (probe error)');
										MODULE._howl = null;
										MODULE._useOscillator = true;
										MODULE._audioValidationFailed = true;
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
										MODULE._howl = null;
										MODULE._useOscillator = true;
										MODULE._audioValidationFailed = true;
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
			// If we already created an AudioContext, try to resume it.
			if (MODULE._audioCtx) {
				if (MODULE._audioCtx.state === 'suspended') {
					await MODULE._audioCtx.resume();
				}
			} else {
				// Only create AudioContext when we have a user gesture (or when caller forces it).
				if (!forceGesture) {
					// Avoid creating AudioContext now — browsers will reject it and log the error.
					return false;
				}
				MODULE._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
				MODULE._gain = MODULE._audioCtx.createGain();
				MODULE._gain.connect(MODULE._audioCtx.destination);
				MODULE._gain.gain.value = 0.0001; // nearly silent for the unlock attempt
				if (MODULE._audioCtx.state === 'suspended') {
					await MODULE._audioCtx.resume();
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
		if (MODULE._autoUnlockInitialized) return;
		MODULE._autoUnlockInitialized = true;

		const tryOnce = async (force = false) => {
			try {
				const ok = await MODULE.tryUnlock(force);
				if (ok) {
					// successful unlock - remove listeners
					removeListeners();
				}
			} catch (e) { /* ignore */ }
		};

		const events = ['click','keydown','pointerdown','pointermove','touchstart','visibilitychange','focus'];
		const gestureEvents = new Set(['click','keydown','pointerdown','touchstart']);
		const listener = function(ev){
			// On visibilitychange, only attempt when visible
			if (ev.type === 'visibilitychange' && document.visibilityState !== 'visible') return;
			const isGesture = gestureEvents.has(ev.type);
			tryOnce(isGesture);
		};

		const removeListeners = function(){
			events.forEach(ev => {
				window.removeEventListener(ev, listener, true);
				document.removeEventListener(ev, listener, true);
			});
		};

		// Attach listeners both on window and document to catch different platforms
		events.forEach(ev => {
			window.addEventListener(ev, listener, { passive: true, capture: true });
			document.addEventListener(ev, listener, { passive: true, capture: true });
		});

		// Also attempt again after short intervals (best-effort). Do NOT create AudioContext immediately.
		tryOnce(false);
		let attempts = 0;
		const intervalId = setInterval(async () => {
			attempts++;
			if (MODULE.userInteracted) {
				clearInterval(intervalId);
				removeListeners();
				return;
			}
			await tryOnce(false);
			if (attempts > 6) { // stop after ~6 attempts (~30s)
				clearInterval(intervalId);
			}
		}, 5000);
	};

	MODULE.playForNewRegistrations = function(newCount, oldCount){
		// Suppress sound if navigation just occurred to avoid playing on nav clicks
		try {
			if (window.NotificationNavClickSuppressed) {
				console.debug('Notification suppressed due to nav click');
				return;
			}
		} catch (e) {}
		const delta = Math.max(1, (newCount - oldCount));
		const times = Math.min(delta, 5); // max 5 rings

		// Play sound: prefer Howler
		if (MODULE._howl) {
			_playHowler(times);
		} else {
			_playOscillator(times);
		}

		// Desktop notification
		try {
			if ('Notification' in window && Notification.permission === 'granted') {
				const notif = new Notification('New Pending Registration', {
					body: `${delta} new registration${delta>1? 's':''} awaiting review.`,
					icon: 'assets/icons/notification.png'
				});
				// Auto-close after 8s
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

})(window);

