<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

class AppConfig
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected array $_values = [];

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct()
    {
        $this->_values = [
            'appName' => $this->_env('APP_NAME', 'EphemPaste'),
            'appBaseUrl' => rtrim($this->_env('APP_BASE_URL', 'http://localhost:8080'), '/'),
            'appEnv' => $this->_env('APP_ENV', 'production'),
            'appDebug' => $this->_envBool('APP_DEBUG', false),
            'pasteCodeLength' => $this->_envInt('PASTE_CODE_LENGTH', 6, 4, 32),
            'pasteMaxLength' => $this->_envInt('PASTE_MAX_LENGTH', 5000, 1, 5000),
            'pasteLifetimeHours' => $this->_envInt('PASTE_LIFETIME_HOURS', 72, 1, 720),
            'pasteCodeCharset' => $this->_env(
                'PASTE_CODE_CHARSET',
                'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.~'
            ),
            'databaseHost' => $this->_requiredEnv('DB_HOST'),
            'databasePort' => $this->_envInt('DB_PORT', 5432, 1, 65535),
            'databaseName' => $this->_requiredEnv('DB_NAME'),
            'databaseUser' => $this->_requiredEnv('DB_USER'),
            'databasePassword' => $this->_requiredEnv('DB_PASSWORD'),
        ];

        if (strlen($this->_values['pasteCodeCharset']) < 2) {
            throw new RuntimeException('PASTE_CODE_CHARSET must contain at least two characters.');
        }

        if (count(array_unique(str_split($this->_values['pasteCodeCharset']))) !== strlen($this->_values['pasteCodeCharset'])) {
            throw new RuntimeException('PASTE_CODE_CHARSET must not contain duplicate characters.');
        }
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _env(string $name, ?string $default = null): string
    {
        $value = getenv($name);

        if ($value === false) {
            if ($default === null) {
                throw new RuntimeException("Missing required environment variable: {$name}");
            }

            return $default;
        }

        // Compose/.env mistakes such as trailing spaces should not silently turn
        // into different database names, URLs, booleans, or credentials.
        $value = trim($value);

        if ($value === '') {
            if ($default === null) {
                throw new RuntimeException("Environment variable {$name} must not be empty.");
            }

            return $default;
        }

        return $value;
    }

    protected function _requiredEnv(string $name): string
    {
        return $this->_env($name);
    }

    protected function _envInt(string $name, int $default, int $min, int $max): int
    {
        $value = $this->_env($name, (string) $default);

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("Environment variable {$name} must be an integer.");
        }

        $integer = (int) $value;

        if ($integer < $min || $integer > $max) {
            throw new RuntimeException("Environment variable {$name} must be between {$min} and {$max}.");
        }

        return $integer;
    }

    protected function _envBool(string $name, bool $default): bool
    {
        $value = strtolower($this->_env($name, $default ? 'true' : 'false'));
        $trueValues = ['1', 'true', 'yes', 'on'];
        $falseValues = ['0', 'false', 'no', 'off'];

        if (in_array($value, $trueValues, true)) {
            return true;
        }

        if (in_array($value, $falseValues, true)) {
            return false;
        }

        throw new RuntimeException(
            "Environment variable {$name} must be one of: true, false, 1, 0, yes, no, on, off."
        );
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->_values)) {
            throw new RuntimeException("Unknown configuration key: {$key}");
        }

        return $this->_values[$key];
    }
}
