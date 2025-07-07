<?php
namespace ZbEmailValidator;

class Logger {
    public static function log(string $message): void {
        if (Settings::is_logging_enabled()) {
            error_log("[ZBV] " . $message);
        }
    }
}
