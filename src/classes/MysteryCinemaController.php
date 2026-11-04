
<?php

require_once __DIR__ . "/MysteryGenreManager.php";

class MysteryCinemaController extends AdminPageController
{
    private MysteryGenreManager $genreManager;

    private array $genres = [];

    private array $selectedGenreIds = [];

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

        
        $this->genres = $this->genreManager->getAll();

        
        $this->selectedGenreIds = $_SESSION["mystery_genre_ids"] ?? [];

        if ($requestMethod === "POST") {
            $this->handleGenreSelection($post);
        }
    }

    
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

        // Uloženie výberu do session.
        $_SESSION["mystery_genre_ids"] = $validGenreIds;
        $_SESSION["mystery_genre_names"] = $validGenreNames;

        $this->selectedGenreIds = $validGenreIds;

        $this->message = "Your favourite genres have been saved successfully.";
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

   
    public function getSelectedGenreIds(): array
    {
        return $this->selectedGenreIds;
    }
}