<?php
require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/MysteryGenreManager.php";
require_once __DIR__ . "/MysteryMovieManager.php";

class MysteryCinemaController extends AdminPageController
{
    private MysteryGenreManager $genreManager;
    private MysteryMovieManager $movieManager;

    private array $genres = [];
    private array $selectedGenreIds = [];
    private array $movies = [];
    private array $selectedMovieIds = [];

    public function __construct(
        mysqli $connection,
        array $get,
        array $post,
        string $requestMethod
    ) {
        parent::__construct(
            $connection,
            $get,
            $post,
            $requestMethod
        );

        $this->genreManager = new MysteryGenreManager($connection);
        $this->movieManager = new MysteryMovieManager($connection);

        $this->genres = $this->genreManager->getAll();

        $this->selectedGenreIds = $_SESSION["mystery_genre_ids"] ?? [];

        if ($requestMethod === "POST") {
            $this->handleGenreSelection($post);
        }

        // Load movies based on selected genres.
        if (!empty($this->selectedGenreIds)) {
            $this->movies = $this->movieManager->getByGenres(
                $this->selectedGenreIds
            );
        }

        // Load previously selected movies.
        $this->selectedMovieIds = $_SESSION["mystery_movie_ids"] ?? [];

        if ($requestMethod === "POST") {
            $this->handleMovieSelection($post);
        }
    }

    /**
     * Handles genre selection.
     */
    private function handleGenreSelection(array $post): void
    {
        if (($post["action"] ?? "") !== "select_genres") {
            return;
        }

        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $genreIds = $post["genre_ids"] ?? [];

        if (!is_array($genreIds) || empty($genreIds)) {
            $this->error = "Vyber aspoň jeden žáner.";
            return;
        }

        $validGenreIds = [];
        $validGenreNames = [];

        foreach ($genreIds as $genreId) {
            $genreId = filter_var($genreId, FILTER_VALIDATE_INT);

            if ($genreId === false || $genreId <= 0) {
                $this->error = "Výber obsahuje neplatný žáner.";
                return;
            }

            $genre = $this->genreManager->findById($genreId);

            if ($genre === null) {
                $this->error = "Jeden z vybraných žánrov neexistuje.";
                return;
            }

            if (!in_array($genreId, $validGenreIds, true)) {
                $validGenreIds[] = $genreId;
                $validGenreNames[] = $genre["genre_name"];
            }
        }

        if (empty($validGenreIds)) {
            $this->error = "Vyber aspoň jeden platný žáner.";
            return;
        }

        $_SESSION["mystery_genre_ids"] = $validGenreIds;
        $_SESSION["mystery_genre_names"] = $validGenreNames;

        // Reset movie selection when genres change.
        unset($_SESSION["mystery_movie_ids"]);

        $this->selectedGenreIds = $validGenreIds;
        $this->selectedMovieIds = [];

        $this->message = "Your favourite genres have been saved successfully.";
    }

    /**
     * Handles selection of exactly three movies.
     */
    private function handleMovieSelection(array $post): void
    {
        if (($post["action"] ?? "") !== "select_movies") {
            return;
        }

        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $movieIds = $post["movie_ids"] ?? [];

        if (!is_array($movieIds) || count($movieIds) !== 3) {
            $this->error = "Vyber presne 3 filmy.";
            return;
        }

        $validMovieIds = [];

        foreach ($movieIds as $movieId) {
            $movieId = filter_var($movieId, FILTER_VALIDATE_INT);

            if ($movieId === false || $movieId <= 0) {
                $this->error = "Výber obsahuje neplatný film.";
                return;
            }

            if (!in_array($movieId, $validMovieIds, true)) {
                $validMovieIds[] = $movieId;
            }
        }

        if (count($validMovieIds) !== 3) {
            $this->error = "Vyber presne 3 rôzne filmy.";
            return;
        }

        // Check whether all selected movies are available
        // and belong to the genres selected by the user.
        $availableMovieIds = array_map(
            fn($movie) => (int) $movie["movie_id"],
            $this->movies
        );

        foreach ($validMovieIds as $movieId) {
            if (!in_array($movieId, $availableMovieIds, true)) {
                $this->error = "Jeden z vybraných filmov nie je dostupný.";
                return;
            }
        }

        $_SESSION["mystery_movie_ids"] = $validMovieIds;

        $this->selectedMovieIds = $validMovieIds;

        $this->message = "Your favourite films have been saved successfully.";
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function getSelectedGenreIds(): array
    {
        return $this->selectedGenreIds;
    }

    public function getMovies(): array
    {
        return $this->movies;
    }

    public function getSelectedMovieIds(): array
    {
        return $this->selectedMovieIds;
    }
}
