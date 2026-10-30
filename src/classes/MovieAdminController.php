<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/MovieManager.php";

class MovieAdminController extends AdminPageController
{
    public bool $isCreating;
    public ?int $selectedMovieId;
    public ?array $movie = null;
    public array $genres = [];
    public array $movies = [];
    public array $selectedGenreIds = [];

    private MovieManager $movieManager;

    public function __construct(mysqli $connection, array $query, array $post, string $requestMethod)
    {
        parent::__construct($connection);

        $this->movieManager = new MovieManager($connection);
        $this->genres = $this->movieManager->getAllGenres();
        $this->isCreating = ($query["create"] ?? "") === "1";
        $this->selectedMovieId = $this->positiveInteger($query["id"] ?? null);

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->loadSelectedMovie();
        $this->movies = $this->movieManager->getAll();
    }

    private function handlePost(array $post): void
    {
        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $action = isset($post["action"]) && is_string($post["action"]) ? $post["action"] : "";
        if ($action === "delete") {
            $this->deleteMovie($post);
            return;
        }

        if ($action !== "create" && $action !== "update") {
            http_response_code(400);
            $this->error = "Invalid movie action.";
            return;
        }

        $this->isCreating = $action === "create";
        $this->selectedMovieId = $this->isCreating
            ? null
            : $this->positiveInteger($post["movie_id"] ?? null);

        $movie = [
            "movie_title" => $this->postedString($post, "movie_title"),
            "movie_description" => $this->postedString($post, "movie_description"),
            "movie_duration" => $this->postedString($post, "movie_duration"),
            "movie_release_date" => $this->postedString($post, "movie_release_date"),
            "movie_age_rating" => $this->postedString($post, "movie_age_rating"),
            "movie_language" => $this->postedString($post, "movie_language"),
            "movie_poster_url" => $this->postedString($post, "movie_poster_url"),
            "movie_is_available" => isset($post["movie_is_available"]) ? 1 : 0,
        ];
        $this->movie = $movie;
        $this->selectedGenreIds = $this->getPostedGenreIds($post["genre_ids"] ?? []);

        $duration = filter_var($movie["movie_duration"], FILTER_VALIDATE_INT);
        $date = DateTime::createFromFormat("!Y-m-d", $movie["movie_release_date"]);
        $dateIsValid = $date && $date->format("Y-m-d") === $movie["movie_release_date"];

        if (!$this->isCreating && $this->selectedMovieId === null) {
            $this->error = "Select a valid movie.";
        } elseif ($movie["movie_title"] === "" || strlen($movie["movie_title"]) > 255) {
            $this->error = "The title is required and must be no more than 255 characters.";
        } elseif ($duration === false || $duration < 1) {
            $this->error = "Duration must be a positive number of minutes.";
        } elseif (!$dateIsValid) {
            $this->error = "Enter a valid release date.";
        } elseif ($movie["movie_age_rating"] === "" || strlen($movie["movie_age_rating"]) > 10) {
            $this->error = "Age rating is required and must be no more than 10 characters.";
        } elseif ($movie["movie_language"] === "" || strlen($movie["movie_language"]) > 50) {
            $this->error = "Language is required and must be no more than 50 characters.";
        } elseif (strlen($movie["movie_poster_url"]) > 255) {
            $this->error = "Poster URL must be no more than 255 characters.";
        } elseif ($movie["movie_poster_url"] !== "" && filter_var($movie["movie_poster_url"], FILTER_VALIDATE_URL) === false) {
            $this->error = "Enter a valid poster URL.";
        } else {
            try {
                $this->selectedMovieId = $this->movieManager->save(
                    $movie,
                    $duration,
                    $this->selectedGenreIds,
                    $this->selectedMovieId
                );
                $successParameter = $this->isCreating ? "created=1" : "saved=1";
                $this->redirectTo(
                    "manage_movies.php?id=" . $this->selectedMovieId . "&" . $successParameter
                );
            } catch (DomainException $exception) {
                $this->error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                error_log("Movie save failed: " . $exception->getMessage());
                $this->error = "Could not save the movie. Check the server log for details.";
            }
        }
    }

    private function deleteMovie(array $post): void
    {
        $movieId = $this->positiveInteger($post["movie_id"] ?? null);
        if ($movieId === null) {
            $this->error = "Select a valid movie to delete.";
            return;
        }

        try {
            if ($this->movieManager->delete($movieId)) {
                $this->redirectTo("manage_movies.php?deleted=1");
            }

            $this->error = "That movie could not be found.";
        } catch (mysqli_sql_exception $exception) {
            error_log("Movie deletion failed: " . $exception->getMessage());
            $this->error = "Could not delete the movie. Check the server log for details.";
        }
    }

    private function postedString(array $post, string $key): string
    {
        return isset($post[$key]) && is_string($post[$key]) ? trim($post[$key]) : "";
    }

    private function getPostedGenreIds(mixed $postedGenreIds): array
    {
        if (!is_array($postedGenreIds)) {
            return [];
        }

        $selectedGenreIds = [];
        foreach ($postedGenreIds as $genreId) {
            $validGenreId = $this->positiveInteger($genreId);
            if ($validGenreId !== null && isset($this->genres[$validGenreId])) {
                $selectedGenreIds[] = $validGenreId;
            }
        }

        return array_values(array_unique($selectedGenreIds));
    }

    private function setSuccessMessage(array $query): void
    {
        if (isset($query["saved"])) {
            $this->message = "Movie changes saved.";
        }
        if (isset($query["created"])) {
            $this->message = "Movie created.";
        } elseif (isset($query["deleted"])) {
            $this->message = "Movie deleted.";
        }
    }

    private function loadSelectedMovie(): void
    {
        if ($this->movie === null && $this->selectedMovieId !== null) {
            $this->movie = $this->movieManager->findById($this->selectedMovieId);
            if ($this->movie !== null) {
                $this->selectedGenreIds = $this->movieManager->getGenreIds($this->selectedMovieId);
            } elseif ($this->error === "") {
                $this->error = "That movie could not be found.";
            }
        }

        if ($this->isCreating && $this->movie === null) {
            $this->movie = [
                "movie_title" => "",
                "movie_description" => "",
                "movie_duration" => "",
                "movie_release_date" => "",
                "movie_age_rating" => "",
                "movie_language" => "",
                "movie_poster_url" => "",
                "movie_is_available" => 1,
            ];
        }
    }
}
