<?php

class ScreeningManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getMovies(): array
    {
        $result = $this->connection->query(
            "SELECT movie_id, movie_title, movie_duration, movie_is_available
             FROM MOVIES
             ORDER BY movie_title"
        );
        $movies = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $movies;
    }

    public function getHalls(): array
    {
        $result = $this->connection->query(
            "SELECT hall_id, hall_name, is_active FROM halls ORDER BY hall_name"
        );
        $halls = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $halls;
    }

    public function getTicketTypes(): array
    {
        $result = $this->connection->query(
            "SELECT ticket_type_id, ticket_type_name FROM ticket_types ORDER BY ticket_type_name"
        );
        $ticketTypes = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $ticketTypes;
    }

    public function getAll(): array
    {
        $result = $this->connection->query(
            "SELECT screening.screening_id, screening.movie_id, screening.hall_id,
                    screening.start_time, screening.end_time, screening.screening_type,
                    screening.status, movie.movie_title, hall.hall_name
             FROM screenings AS screening
             LEFT JOIN MOVIES AS movie ON movie.movie_id = screening.movie_id
             INNER JOIN halls AS hall ON hall.hall_id = screening.hall_id
             ORDER BY screening.start_time DESC
             LIMIT 100"
        );
        $screenings = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $screenings;
    }

    public function findById(int $screeningId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT screening_id, movie_id, hall_id, start_time, end_time,
                    screening_type, status
             FROM screenings
             WHERE screening_id = ?"
        );
        $statement->bind_param("i", $screeningId);
        $statement->execute();
        $screening = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $screening;
    }

    public function getPrices(int $screeningId): array
    {
        $statement = $this->connection->prepare(
            "SELECT ticket_type_id, ticket_price
             FROM screening_ticket_prices
             WHERE screening_id = ?"
        );
        $statement->bind_param("i", $screeningId);
        $statement->execute();
        $result = $statement->get_result();
        $prices = [];
        while ($price = $result->fetch_assoc()) {
            $prices[(int) $price["ticket_type_id"]] = (string) $price["ticket_price"];
        }
        $statement->close();

        return $prices;
    }

    public function hasHallConflict(
        int $hallId,
        string $startTime,
        string $endTime,
        ?int $excludingScreeningId = null
    ): bool {
        $sql = "SELECT screening_id
                FROM screenings
                WHERE hall_id = ?
                  AND status = 'scheduled'
                  AND start_time < ?
                  AND end_time > ?";
        if ($excludingScreeningId !== null) {
            $sql .= " AND screening_id <> ?";
        }
        $sql .= " LIMIT 1";

        $statement = $this->connection->prepare($sql);
        if ($excludingScreeningId === null) {
            $statement->bind_param("iss", $hallId, $endTime, $startTime);
        } else {
            $statement->bind_param(
                "issi",
                $hallId,
                $endTime,
                $startTime,
                $excludingScreeningId
            );
        }
        $statement->execute();
        $hasConflict = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $hasConflict;
    }

    public function save(
        array $screening,
        string $endTime,
        array $prices,
        ?int $screeningId
    ): int {
        $this->connection->begin_transaction();

        try {
            if ($screeningId === null) {
                $statement = $this->connection->prepare(
                    "INSERT INTO screenings
                     (movie_id, hall_id, start_time, end_time, screening_type, status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $statement->bind_param(
                    "iissss",
                    $screening["movie_id"],
                    $screening["hall_id"],
                    $screening["start_time"],
                    $endTime,
                    $screening["screening_type"],
                    $screening["status"]
                );
                $statement->execute();
                $screeningId = (int) $this->connection->insert_id;
                $statement->close();
            } else {
                if ($this->findById($screeningId) === null) {
                    throw new DomainException("That screening could not be found.");
                }

                $statement = $this->connection->prepare(
                    "UPDATE screenings
                     SET movie_id = ?, hall_id = ?, start_time = ?, end_time = ?,
                         screening_type = ?, status = ?
                     WHERE screening_id = ?"
                );
                $statement->bind_param(
                    "iissssi",
                    $screening["movie_id"],
                    $screening["hall_id"],
                    $screening["start_time"],
                    $endTime,
                    $screening["screening_type"],
                    $screening["status"],
                    $screeningId
                );
                $statement->execute();
                $statement->close();

                $statement = $this->connection->prepare(
                    "DELETE FROM screening_ticket_prices WHERE screening_id = ?"
                );
                $statement->bind_param("i", $screeningId);
                $statement->execute();
                $statement->close();
            }

            if ($prices !== []) {
                $statement = $this->connection->prepare(
                    "INSERT INTO screening_ticket_prices
                     (screening_id, ticket_type_id, ticket_price)
                     VALUES (?, ?, ?)"
                );
                foreach ($prices as $ticketTypeId => $price) {
                    $statement->bind_param("iid", $screeningId, $ticketTypeId, $price);
                    $statement->execute();
                }
                $statement->close();
            }

            $this->connection->commit();
            return $screeningId;
        } catch (Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function delete(int $screeningId): bool
    {
        $statement = $this->connection->prepare(
            "SELECT COUNT(*) AS booking_count FROM bookings WHERE screening_id = ?"
        );
        $statement->bind_param("i", $screeningId);
        $statement->execute();
        $bookingCount = (int) $statement->get_result()->fetch_assoc()["booking_count"];
        $statement->close();

        if ($bookingCount > 0) {
            throw new DomainException(
                "This screening has bookings and cannot be deleted. Change its status to cancelled instead."
            );
        }

        $statement = $this->connection->prepare(
            "DELETE FROM screenings WHERE screening_id = ?"
        );
        $statement->bind_param("i", $screeningId);
        $statement->execute();
        $deleted = $statement->affected_rows === 1;
        $statement->close();

        return $deleted;
    }
}
