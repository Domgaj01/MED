<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/SiteContentManager.php";

class SiteContentAdminController extends AdminPageController
{
    public array $settings = [];
    public array $openingHours = [];
    public array $news = [];
    public ?array $selectedNews = null;
    public ?int $selectedNewsId = null;
    public bool $isCreatingNews = false;

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
        $this->selectedNewsId = $this->positiveInteger($query["news_id"] ?? null);
        $this->isCreatingNews = ($query["create_news"] ?? "") === "1";

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
        $this->news = $this->contentManager->getNews();

        if ($this->selectedNewsId !== null && $this->selectedNews === null) {
            $this->selectedNews = $this->contentManager->findNewsById($this->selectedNewsId);
            if ($this->selectedNews === null && $this->error === "") {
                $this->error = "That news article could not be found.";
            }
        }

        if ($this->isCreatingNews && $this->selectedNews === null) {
            $this->selectedNews = [
                "title" => "",
                "body" => "",
                "is_published" => 0,
            ];
        }
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

        if ($action === "delete_news") {
            $this->deleteNews($post);
            return;
        }

        if ($action === "create_news" || $action === "update_news") {
            $this->saveNews($post, $action === "create_news");
            return;
        }

        http_response_code(400);
        $this->error = "Invalid content action.";
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

    private function getPostedOpeningHours(mixed $postedHours): ?array
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

    private function saveNews(array $post, bool $isCreating): void
    {
        $newsId = $isCreating
            ? null
            : $this->positiveInteger($post["news_id"] ?? null);
        $article = [
            "title" => $this->postedString($post, "title"),
            "body" => $this->postedString($post, "body"),
            "is_published" => isset($post["is_published"]) && $post["is_published"] === "1" ? 1 : 0,
        ];
        $this->isCreatingNews = $isCreating;
        $this->selectedNewsId = $newsId;
        $this->selectedNews = $article;

        if (!$isCreating && $newsId === null) {
            $this->error = "Choose a valid news article to edit.";
        } elseif ($article["title"] === "" || mb_strlen($article["title"], "UTF-8") > 200) {
            $this->error = "Title is required and must be no more than 200 characters.";
        } elseif ($article["body"] === "" || mb_strlen($article["body"], "UTF-8") > 60000) {
            $this->error = "Article text is required and must be no more than 60,000 characters.";
        } else {
            try {
                $this->selectedNewsId = $this->contentManager->saveNews(
                    $article,
                    $newsId
                );
                $parameter = $isCreating ? "created=1" : "news_saved=1";
                $this->redirectTo(
                    "manage_site.php?news_id=" . $this->selectedNewsId . "&" . $parameter . "#news"
                );
            } catch (DomainException $exception) {
                $this->error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                error_log("News save failed: " . $exception->getMessage());
                $this->error = "Could not save the news article. Check the server log for details.";
            }
        }
    }

    private function deleteNews(array $post): void
    {
        $newsId = $this->positiveInteger($post["news_id"] ?? null);
        if ($newsId === null) {
            $this->error = "Choose a valid news article to delete.";
            return;
        }

        try {
            if ($this->contentManager->deleteNews($newsId)) {
                $this->redirectTo("manage_site.php?deleted=1#news");
            }
            $this->error = "That news article could not be found.";
        } catch (mysqli_sql_exception $exception) {
            error_log("News deletion failed: " . $exception->getMessage());
            $this->error = "Could not delete the news article. Check the server log for details.";
        }
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
        } elseif (isset($query["created"])) {
            $this->message = "News article created.";
        } elseif (isset($query["news_saved"])) {
            $this->message = "News article changes saved.";
        } elseif (isset($query["deleted"])) {
            $this->message = "News article deleted.";
        }
    }
}
