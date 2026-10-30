<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/GenreManager.php";

class GenreAdminController extends AdminPageController
{
    public bool $isCreating;
    public ?int $genreId;
    public ?array $genre = null;
    public array $genres = [];

    private GenreManager $genreManager;

    public function __construct(mysqli $connection, array $query, array $post, string $requestMethod)
    {
        parent::__construct($connection);

        $this->genreManager = new GenreManager($connection);
        $this->isCreating = ($query["create"] ?? "") === "1";
        $this->genreId = $this->positiveInteger($query["id"] ?? null);

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->loadSelectedGenre();
        $this->genres = $this->genreManager->getAll();
    }

    private function handlePost(array $post): void
    {
        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $action = isset($post["action"]) && is_string($post["action"]) ? $post["action"] : "";
        $this->genreId = $this->positiveInteger($post["genre_id"] ?? null);

        if ($action === "delete") {
            $this->deleteGenre();
            return;
        }

        if ($action !== "create" && $action !== "update") {
            http_response_code(400);
            $this->error = "Invalid genre action.";
            return;
        }

        $this->isCreating = $action === "create";
        $nameInput = $post["genre_name"] ?? "";
        $descriptionInput = $post["genre_description"] ?? "";
        $name = is_string($nameInput) ? trim($nameInput) : "";
        $description = is_string($descriptionInput) ? trim($descriptionInput) : "";
        $this->genre = [
            "genre_name" => $name,
            "genre_description" => $description,
        ];

        if ($name === "" || mb_strlen($name, "UTF-8") > 100) {
            $this->error = "Enter a genre name of 1 to 100 characters.";
            return;
        }

        if (!$this->isCreating && $this->genreId === null) {
            $this->error = "Choose a valid genre to edit.";
            return;
        }

        try {
            $descriptionValue = $description === "" ? null : $description;
            if ($this->isCreating) {
                $this->genreId = $this->genreManager->create($name, $descriptionValue);
                $this->redirectTo("manage_genres.php?id=" . $this->genreId . "&created=1");
            }

            if ($this->genreManager->findById($this->genreId) === null) {
                $this->error = "That genre could not be found.";
                return;
            }

            $this->genreManager->update($this->genreId, $name, $descriptionValue);
            $this->redirectTo("manage_genres.php?id=" . $this->genreId . "&saved=1");
        } catch (mysqli_sql_exception $exception) {
            error_log("Genre save failed: " . $exception->getMessage());
            $this->error = $exception->getCode() === 1062
                ? "A genre with that name already exists."
                : "Could not save the genre. Check the server log for details.";
        }
    }

    private function deleteGenre(): void
    {
        if ($this->genreId === null) {
            $this->error = "Choose a valid genre to delete.";
            return;
        }

        try {
            if ($this->genreManager->delete($this->genreId)) {
                $this->redirectTo("manage_genres.php?deleted=1");
            }

            $this->error = "That genre could not be found.";
        } catch (mysqli_sql_exception $exception) {
            error_log("Genre deletion failed: " . $exception->getMessage());
            $this->error = "Could not delete the genre. Check the server log for details.";
        }
    }

    private function setSuccessMessage(array $query): void
    {
        if (isset($query["created"])) {
            $this->message = "Genre created.";
        } elseif (isset($query["saved"])) {
            $this->message = "Genre changes saved.";
        } elseif (isset($query["deleted"])) {
            $this->message = "Genre deleted.";
        }
    }

    private function loadSelectedGenre(): void
    {
        if ($this->genre === null && !$this->isCreating && $this->genreId !== null) {
            $this->genre = $this->genreManager->findById($this->genreId);
            if ($this->genre === null && $this->error === "") {
                $this->error = "That genre could not be found.";
            }
        }

        if ($this->isCreating && $this->genre === null) {
            $this->genre = [
                "genre_name" => "",
                "genre_description" => "",
            ];
        }
    }
}
