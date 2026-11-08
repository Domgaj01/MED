<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/SiteContentManager.php";

class SiteContentAdminController extends AdminPageController
{
    public array $settings = [];
    public array $openingHours = [];

    private SiteContentManager $contentManager;

    private const DAYS = [
        1 => "Monday",
        2 => "Tuesday",
        3 => "Wednesday",
        4 => "Thursday",
        5 => "Friday",
        6 => "Saturday",
        7 => "Sunday",
    ];

    public function __construct(mysqli $connection, array $query, array $post, string $requestMethod)
    {
        parent::__construct($connection);

        $this->contentManager = new SiteContentManager($connection);

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->settings = $this->settings !== []
            ? $this->settings
            : $this->contentManager->getSettings();
        $this->openingHours = $this->openingHours !== []
            ? $this->openingHours
            : $this->contentManager->getOpeningHours();
    }

    private function handlePost(array $post): void
    {
        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $action = isset($post["action"]) && is_string($post["action"])
            ? $post["action"]
            : "";

        if ($action === "save_site_content") {
            $this->saveSiteContent($post);
            return;
        }

        http_response_code(400);
        $this->error = "Invalid website content action.";
    }

    private function saveSiteContent(array $post): void
    {
        $settings = [
            "company_description" => $this->postedString($post, "company_description"),
            "contact_address" => $this->postedString($post, "contact_address"),
            "contact_phone" => $this->postedString($post, "contact_phone"),
            "contact_email" => $this->postedString($post, "contact_email"),
        ];
        $openingHours = $this->getPostedOpeningHours($post["opening_hours"] ?? null);
        $this->settings = $settings;
        if ($openingHours !== null) {
            $this->openingHours = $openingHours;
        }

        if ($settings["company_description"] === "" || mb_strlen($settings["company_description"], "UTF-8") > 10000) {
            $this->error = "Company description is required and must be no more than 10,000 characters.";
        } elseif (mb_strlen($settings["contact_address"], "UTF-8") > 255) {
            $this->error = "Address must be no more than 255 characters.";
        } elseif (mb_strlen($settings["contact_phone"], "UTF-8") > 50) {
            $this->error = "Phone number must be no more than 50 characters.";
        } elseif (
            $settings["contact_email"] !== ""
            && (
                mb_strlen($settings["contact_email"], "UTF-8") > 254
                || filter_var($settings["contact_email"], FILTER_VALIDATE_EMAIL) === false
            )
        ) {
            $this->error = "Enter a valid contact email address.";
        } elseif ($openingHours === null) {
            $this->error = "Enter valid opening hours for every day.";
        } else {
            try {
                $this->contentManager->saveSiteContent($settings, $openingHours);
                $this->redirectTo("manage_site.php?saved=1#site-settings");
            } catch (mysqli_sql_exception $exception) {
                error_log("Site content save failed: " . $exception->getMessage());
                $this->error = "Could not save site content. Check the server log for details.";
            }
        }
    }

    private function getPostedOpeningHours($postedHours): ?array
    {
        if (!is_array($postedHours)) {
            return null;
        }

        $openingHours = [];
        foreach (self::DAYS as $day => $label) {
            $dayInput = $postedHours[$day] ?? null;
            if (!is_array($dayInput)) {
                return null;
            }

            $isClosed = isset($dayInput["is_closed"]) && $dayInput["is_closed"] === "1";
            $opensAt = isset($dayInput["opens_at"]) && is_string($dayInput["opens_at"])
                ? trim($dayInput["opens_at"])
                : "";
            $closesAt = isset($dayInput["closes_at"]) && is_string($dayInput["closes_at"])
                ? trim($dayInput["closes_at"])
                : "";

            if (
                !$isClosed
                && (
                    preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $opensAt) !== 1
                    || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $closesAt) !== 1
                    || $opensAt === $closesAt
                )
            ) {
                return null;
            }

            $openingHours[$day] = [
                "day_of_week" => $day,
                "opens_at" => $opensAt,
                "closes_at" => $closesAt,
                "is_closed" => (int) $isClosed,
            ];
        }

        return $openingHours;
    }

    private function postedString(array $post, string $key): string
    {
        return isset($post[$key]) && is_string($post[$key])
            ? trim($post[$key])
            : "";
    }

    private function setSuccessMessage(array $query): void
    {
        if (isset($query["saved"])) {
            $this->message = "Company information and opening hours saved.";
        }
    }
}
