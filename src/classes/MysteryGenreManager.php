<?php

class MysteryGenreManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

   
    public function getAll(): array
    {
        $sql = "
            SELECT genre_id, genre_name, genre_description
            FROM GENRES
            ORDER BY genre_name ASC
        ";

        $result = $this->connection->query($sql);

        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    
    public function findById(int $genreId): ?array
    {
        $sql = "
            SELECT genre_id, genre_name, genre_description
            FROM GENRES
            WHERE genre_id = ?
        ";

        $statement = $this->connection->prepare($sql);

        if (!$statement) {
            return null;
        }

        $statement->bind_param("i", $genreId);
        $statement->execute();

        $result = $statement->get_result();
        $genre = $result->fetch_assoc() ?: null;

        $statement->close();

        return $genre;
    }
}
