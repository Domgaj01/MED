<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_access.php";

if (!logged_in()) {
    redirect_to("login.php");
}

if (!current_user_is_admin($conn)) {
    if (!logged_in()) {
        redirect_to("login.php");
    }

    http_response_code(403);
    exit("Forbidden");
}
