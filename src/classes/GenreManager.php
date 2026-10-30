<?php

class GenreManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getAll(): array
    {
        $result = $this->connection->query(
            "SELECT genre_id, genre_name, genre_description FROM GENRES ORDER BY genre_name"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $genreId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT genre_id, genre_name, genre_description FROM GENRES WHERE genre_id = ?"
        );
        $statement->bind_param("i", $genreId);
        $statement->execute();
        $genre = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $genre;
    }

    public function create(string $name, ?string $description): int
    {
        $statement = $this->connection->prepare(
            "INSERT INTO GENRES (genre_name, genre_description) VALUES (?, ?)"
        );
        $statement->bind_param("ss", $name, $description);
        $statement->execute();
        $genreId = $this->connection->insert_id;
        $statement->close();

        return $genreId;
    }

    public function update(int $genreId, string $name, ?string $description): void
    {
        $statement = $this->connection->prepare(
            "UPDATE GENRES SET genre_name = ?, genre_description = ? WHERE genre_id = ?"
        );
        $statement->bind_param("ssi", $name, $description, $genreId);
        $statement->execute();
        $statement->close();
    }

    public function delete(int $genreId): bool
    {
        $statement = $this->connection->prepare("DELETE FROM GENRES WHERE genre_id = ?");
        $statement->bind_param("i", $genreId);
        $statement->execute();
        $deleted = $statement->affected_rows === 1;
        $statement->close();

        return $deleted;
    }
}
