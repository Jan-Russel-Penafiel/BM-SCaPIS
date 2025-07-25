<?php
/**
 * Settings Helper Class
 * Handles system configuration settings from the database
 */
class Settings {
    private static $instance = null;
    private $pdo;
    private $settings = [];
    private $modified = false;

    /**
     * Constructor - loads all settings from database
     */
    private function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance($pdo = null) {
        if (self::$instance === null) {
            if ($pdo === null) {
                throw new Exception('PDO connection required for first initialization');
            }
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }

    /**
     * Load all settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->prepare("SELECT config_key, config_value FROM system_config");
            $stmt->execute();
            $this->settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Failed to load settings: " . $e->getMessage());
            throw new Exception("Failed to load system settings");
        }
    }

    /**
     * Get a setting value
     */
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Set a setting value
     */
    public function set($key, $value, $autoSave = true) {
        if (!isset($this->settings[$key]) || $this->settings[$key] !== $value) {
            $this->settings[$key] = $value;
            $this->modified = true;

            if ($autoSave) {
                return $this->save($key, $value);
            }
        }
        return true;
    }

    /**
     * Save a specific setting to database
     */
    public function save($key, $value) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO system_config (config_key, config_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = ?
            ");
            $result = $stmt->execute([$key, $value, $value]);

            // Log the change
            if ($result && isset($_SESSION['user_id'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO activity_logs (
                        user_id, action, table_affected, record_id,
                        old_values, new_values, ip_address, user_agent
                    ) VALUES (
                        ?, 'update_setting', 'system_config', ?,
                        ?, ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $key,
                    json_encode(['value' => $this->settings[$key] ?? null]),
                    json_encode(['value' => $value]),
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
            }

            $this->modified = false;
            return $result;
        } catch (PDOException $e) {
            error_log("Failed to save setting {$key}: " . $e->getMessage());
            throw new Exception("Failed to save setting");
        }
    }

    /**
     * Save all modified settings
     */
    public function saveAll() {
        if (!$this->modified) {
            return true;
        }

        try {
            $this->pdo->beginTransaction();

            foreach ($this->settings as $key => $value) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO system_config (config_key, config_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE config_value = ?
                ");
                $stmt->execute([$key, $value, $value]);
            }

            // Log the bulk change
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO activity_logs (
                        user_id, action, table_affected,
                        new_values, ip_address, user_agent
                    ) VALUES (
                        ?, 'update_all_settings', 'system_config',
                        ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    json_encode($this->settings),
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
            }

            $this->pdo->commit();
            $this->modified = false;
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to save all settings: " . $e->getMessage());
            throw new Exception("Failed to save settings");
        }
    }

    /**
     * Delete a setting
     */
    public function delete($key) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM system_config WHERE config_key = ?");
            $result = $stmt->execute([$key]);

            if ($result) {
                unset($this->settings[$key]);

                // Log the deletion
                if (isset($_SESSION['user_id'])) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO activity_logs (
                            user_id, action, table_affected, record_id,
                            old_values, ip_address, user_agent
                        ) VALUES (
                            ?, 'delete_setting', 'system_config', ?,
                            ?, ?, ?
                        )
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $key,
                        json_encode(['key' => $key]),
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]);
                }
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Failed to delete setting {$key}: " . $e->getMessage());
            throw new Exception("Failed to delete setting");
        }
    }

    /**
     * Get all settings
     */
    public function getAll() {
        return $this->settings;
    }

    /**
     * Check if a setting exists
     */
    public function exists($key) {
        return isset($this->settings[$key]);
    }

    /**
     * Get a boolean setting value
     */
    public function getBool($key, $default = false) {
        $value = $this->get($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get an integer setting value
     */
    public function getInt($key, $default = 0) {
        $value = $this->get($key, $default);
        return (int)$value;
    }

    /**
     * Get a float setting value
     */
    public function getFloat($key, $default = 0.0) {
        $value = $this->get($key, $default);
        return (float)$value;
    }

    /**
     * Get a JSON decoded setting value
     */
    public function getJson($key, $default = null) {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }
        return json_decode($value, true);
    }

    /**
     * Set a JSON encoded setting value
     */
    public function setJson($key, $value, $autoSave = true) {
        return $this->set($key, json_encode($value), $autoSave);
    }

    /**
     * Reset settings to default values
     */
    public function resetToDefaults() {
        try {
            $this->pdo->beginTransaction();

            // Delete all current settings
            $stmt = $this->pdo->prepare("DELETE FROM system_config");
            $stmt->execute();

            // Insert default settings
            $defaults = [
                'system_name' => 'BM-SCaPIS',
                'barangay_name' => 'Barangay Malangit',
                'philsms_api_key' => 'your_philsms_api_key_here',
                'philsms_sender_name' => 'BM-SCaPIS',
                'ringtone_enabled' => '1'
            ];

            $stmt = $this->pdo->prepare("
                INSERT INTO system_config (config_key, config_value) 
                VALUES (?, ?)
            ");

            foreach ($defaults as $key => $value) {
                $stmt->execute([$key, $value]);
            }

            // Log the reset
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO activity_logs (
                        user_id, action, table_affected,
                        new_values, ip_address, user_agent
                    ) VALUES (
                        ?, 'reset_settings', 'system_config',
                        ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    json_encode($defaults),
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
            }

            $this->pdo->commit();
            $this->settings = $defaults;
            $this->modified = false;
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to reset settings: " . $e->getMessage());
            throw new Exception("Failed to reset settings");
        }
    }
} 