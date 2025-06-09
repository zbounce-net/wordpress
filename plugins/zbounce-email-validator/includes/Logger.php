<?php
namespace ZbEmailValidator;

class Logger {
    /**
     * Пишет в PHP-лог, если включено в настройках
     */
    public static function log(string $message): void {
        if (Settings::is_logging_enabled()) {
            error_log("[ZBV] " . $message);
        }
    }
}
