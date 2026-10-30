<?php

class MovieManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getAllGenres(): array
    {
        $result = $this->connection->query(
            "SELECT genre_id, genre_name FROM GENRES ORDER BY genre_name"
        );
        $genres = [];

        while ($genre = $result->fetch_assoc()) {
            $genres[(int) $genre["genre_id"]] = $genre["genre_name"];
        }

        $result->free();
        return $genres;
    }

    public function getAll(): array
    {
        $result = $this->connection->query(
            "SELECT movie_id, movie_title, movie_description, movie_duration, movie_release_date,
                    movie_age_rating, movie_language, movie_poster_url, movie_is_available
             FROM MOVIES ORDER BY movie_title"
        );
        $movies = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $movies;
    }

    public function findById(int $movieId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT movie_id, movie_title, movie_description, movie_duration, movie_release_date,
                    movie_age_rating, movie_language, movie_poster_url, movie_is_available
             FROM MOVIES WHERE movie_id = ?"
        );
        $statement->bind_param("i", $movieId);
        $statement->execute();
        $movie = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $movie;
    }

    public function getGenreIds(int $movieId): array
    {
        $statement = $this->connection->prepare(
            "SELECT genre_id FROM MOVIE_GENRES WHERE movie_id = ?"
        );
        $statement->bind_param("i", $movieId);
        $statement->execute();
        $result = $statement->get_result();
        $genreIds = [];

        while ($genre = $result->fetch_assoc()) {
            $genreIds[] = (int) $genre["genre_id"];
        }

        $statement->close();
        return $genreIds;
    }

    public function save(array $movie, int $duration, array $genreIds, ?int $movieId): int
    {
        $this->connection->begin_transaction();

        try {
            $description = $movie["movie_description"] === "" ? null : $movie["movie_description"];
            $posterUrl = $movie["movie_poster_url"] === "" ? null : $movie["movie_poster_url"];
            $availability = (int) $movie["movie_is_available"];

            if ($movieId === null) {
                $statement = $this->connection->prepare(
                    "INSERT INTO MOVIES
                     (movie_title, movie_description, movie_duration, movie_release_date,
                      movie_age_rating, movie_language, movie_poster_url, movie_is_available)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $statement->bind_param(
                    "ssissssi",
                    $movie["movie_title"],
                    $description,
                    $duration,
                    $movie["movie_release_date"],
                    $movie["movie_age_rating"],
                    $movie["movie_language"],
                    $posterUrl,
                    $availability
                );
                $statement->execute();
                $movieId = (int) $this->connection->insert_id;
                $statement->close();
            } else {
                $existingMovie = $this->findById($movieId);
                if ($existingMovie === null) {
                    throw new DomainException("That movie could not be found.");
                }

                $statement = $this->connection->prepare(
                    "UPDATE MOVIES
                     SET movie_title = ?, movie_description = ?, movie_duration = ?,
                         movie_release_date = ?, movie_age_rating = ?, movie_language = ?,
                         movie_poster_url = ?, movie_is_available = ?
                     WHERE movie_id = ?"
                );
                $statement->bind_param(
                    "ssissssii",
                    $movie["movie_title"],
                    $description,
                    $duration,
                    $movie["movie_release_date"],
                    $movie["movie_age_rating"],
                    $movie["movie_language"],
                    $posterUrl,
                    $availability,
                    $movieId
                );
                $statement->execute();
                $statement->close();

                $statement = $this->connection->prepare(
                    "DELETE FROM MOVIE_GENRES WHERE movie_id = ?"
                );
                $statement->bind_param("i", $movieId);
                $statement->execute();
                $statement->close();
            }

            if ($genreIds !== []) {
                $statement = $this->connection->prepare(
                    "INSERT INTO MOVIE_GENRES (movie_id, genre_id) VALUES (?, ?)"
                );
                foreach ($genreIds as $genreId) {
                    $statement->bind_param("ii", $movieId, $genreId);
                    $statement->execute();
                }
                $statement->close();
            }

            $this->connection->commit();
            return $movieId;
        } catch (Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function delete(int $movieId): bool
    {
        $statement = $this->connection->prepare("DELETE FROM MOVIES WHERE movie_id = ?");
        $statement->bind_param("i", $movieId);
        $statement->execute();
        $deleted = $statement->affected_rows === 1;
        $statement->close();

        return $deleted;
    }
}
