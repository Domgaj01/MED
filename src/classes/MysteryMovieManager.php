<?php

class MysteryMovieManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns available movies matching selected genres.
     */
    public function getByGenres(array $genreIds): array
    {
        if (empty($genreIds)) {
            return [];
        }

        // Keep only valid positive integer IDs.
        $genreIds = array_values(array_unique(array_filter(
            array_map('intval', $genreIds),
            fn($id) => $id > 0
        )));

        if (empty($genreIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));

        $sql = "
            SELECT DISTINCT
                m.movie_id,
                m.movie_title,
                m.movie_description,
                m.movie_duration,
                m.movie_release_date,
                m.movie_age_rating,
                m.movie_language,
                m.movie_poster_url
            FROM MOVIES m
            INNER JOIN MOVIE_GENRES mg
                ON m.movie_id = mg.movie_id
            WHERE m.movie_is_available = 1
              AND mg.genre_id IN ($placeholders)
            ORDER BY m.movie_title ASC
        ";

        $statement = $this->connection->prepare($sql);

        if (!$statement) {
            return [];
        }

        $types = str_repeat('i', count($genreIds));
        $statement->bind_param($types, ...$genreIds);

        if (!$statement->execute()) {
            $statement->close();
            return [];
        }

        $result = $statement->get_result();
        $movies = $result->fetch_all(MYSQLI_ASSOC);

        $statement->close();

        return $movies;
    }

    /**
     * Returns one movie by ID.
     */
    public function findById(int $movieId): ?array
    {
        $sql = "
            SELECT
                movie_id,
                movie_title,
                movie_description,
                movie_duration,
                movie_release_date,
                movie_age_rating,
                movie_language,
                movie_poster_url
            FROM MOVIES
            WHERE movie_id = ?
              AND movie_is_available = 1
        ";

        $statement = $this->connection->prepare($sql);

        if (!$statement) {
            return null;
        }

        $statement->bind_param("i", $movieId);
        $statement->execute();

        $result = $statement->get_result();
        $movie = $result->fetch_assoc() ?: null;

        $statement->close();

        return $movie;
    }
}
