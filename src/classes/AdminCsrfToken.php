<?php

class AdminCsrfToken
{
    private const SESSION_KEY = "admin_csrf_token";

    public static function get(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function isValid(mixed $token): bool
    {
        return is_string($token) && hash_equals(self::get(), $token);
    }
}
