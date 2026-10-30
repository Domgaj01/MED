<?php

require_once __DIR__ . "/AdminCsrfToken.php";

abstract class AdminPageController
{
    public string $message = "";
    public string $error = "";
    public string $csrfToken;

    protected mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
        $this->csrfToken = AdminCsrfToken::get();
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }

    protected function redirectTo(string $location): void
    {
        redirect_to($location);
    }

    protected function verifyCsrfToken(array $post): bool
    {
        if (AdminCsrfToken::isValid($post["csrf_token"] ?? null)) {
            return true;
        }

        http_response_code(400);
        $this->error = "Your form session expired. Reload the page and try again.";
        return false;
    }

    protected function positiveInteger(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer !== false && $integer > 0 ? $integer : null;
    }
}
