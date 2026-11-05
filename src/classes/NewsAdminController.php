<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/SiteContentManager.php";

class NewsAdminController extends AdminPageController
{
    public array $articles = [];
    public ?array $article = null;
    public ?int $articleId = null;
    public bool $isCreating = false;

    private SiteContentManager $contentManager;

    public function __construct(mysqli $connection, array $query, array $post, string $requestMethod)
    {
        parent::__construct($connection);

        $this->contentManager = new SiteContentManager($connection);
        $this->articleId = $this->positiveInteger($query["id"] ?? null);
        $this->isCreating = ($query["create"] ?? "") === "1";

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->articles = $this->contentManager->getNews();

        if ($this->articleId !== null && $this->article === null) {
            $this->article = $this->contentManager->findNewsById($this->articleId);
            if ($this->article === null && $this->error === "") {
                $this->error = "That news article could not be found.";
            }
        }

        if ($this->isCreating && $this->article === null) {
            $this->article = [
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

        if ($action === "delete") {
            $this->deleteArticle($post);
            return;
        }

        if ($action === "create" || $action === "update") {
            $this->saveArticle($post, $action === "create");
            return;
        }

        http_response_code(400);
        $this->error = "Invalid news action.";
    }

    private function saveArticle(array $post, bool $isCreating): void
    {
        $articleId = $isCreating
            ? null
            : $this->positiveInteger($post["news_id"] ?? null);
        $article = [
            "title" => $this->postedString($post, "title"),
            "body" => $this->postedString($post, "body"),
            "is_published" => isset($post["is_published"]) && $post["is_published"] === "1" ? 1 : 0,
        ];
        $this->isCreating = $isCreating;
        $this->articleId = $articleId;
        $this->article = $article;

        if (!$isCreating && $articleId === null) {
            $this->error = "Choose a valid news article to edit.";
        } elseif ($article["title"] === "" || mb_strlen($article["title"], "UTF-8") > 200) {
            $this->error = "Title is required and must be no more than 200 characters.";
        } elseif ($article["body"] === "" || mb_strlen($article["body"], "UTF-8") > 60000) {
            $this->error = "Article text is required and must be no more than 60,000 characters.";
        } else {
            try {
                $this->articleId = $this->contentManager->saveNews($article, $articleId);
                $parameter = $isCreating ? "created=1" : "saved=1";
                $this->redirectTo("manage_news.php?id=" . $this->articleId . "&" . $parameter);
            } catch (DomainException $exception) {
                $this->error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                error_log("News save failed: " . $exception->getMessage());
                $this->error = "Could not save the news article. Check the server log for details.";
            }
        }
    }

    private function deleteArticle(array $post): void
    {
        $articleId = $this->positiveInteger($post["news_id"] ?? null);
        if ($articleId === null) {
            $this->error = "Choose a valid news article to delete.";
            return;
        }

        try {
            if ($this->contentManager->deleteNews($articleId)) {
                $this->redirectTo("manage_news.php?deleted=1");
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
        if (isset($query["created"])) {
            $this->message = "News article created.";
        } elseif (isset($query["saved"])) {
            $this->message = "News article changes saved.";
        } elseif (isset($query["deleted"])) {
            $this->message = "News article deleted.";
        }
    }
}
