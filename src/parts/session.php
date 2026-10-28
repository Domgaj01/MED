<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect_to(string $location): void
{
    header("Location: " . $location);
    exit;
}

function logged_in(): bool
{
    return isset($_SESSION["user_id"]);
}

function confirm_logged_in(): void
{
    if (!logged_in()) {
        redirect_to("login.php");
    }
}