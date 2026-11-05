<?php
require_once __DIR__ . "/session.php";

function current_user_is_admin(mysqli $connection): bool
{
    static $checked = false;
    static $isAdmin = false;

    if ($checked) {
        return $isAdmin;
    }

    $checked = true;
    if (!logged_in()) {
        return false;
    }

    $sessionUserId = $_SESSION["user_id"];
    $userId = is_int($sessionUserId) || is_string($sessionUserId)
        ? filter_var($sessionUserId, FILTER_VALIDATE_INT)
        : false;
    if (!is_int($userId) || $userId < 1) {
        $_SESSION = [];
        return false;
    }

    $stmt = $connection->prepare("SELECT role, is_active FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($role, $isActive);
    $userExists = $stmt->fetch();
    $stmt->close();

    if (!$userExists || !(bool) $isActive) {
        $_SESSION = [];
        return false;
    }

    $isAdmin = $role === "admin";
    return $isAdmin;
}
