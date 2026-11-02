<?php

class BookingScheduleManager
{
    // connection
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getMovies(): array
    {
        // only movies that are available now
        $result = $this->connection->query(
            "SELECT movie_id, movie_title
             FROM MOVIES
             WHERE movie_is_available = 1
             ORDER BY movie_title"
        );
        $movies = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $movies;
    }

    public function getScreenings(int $movieId, string $date): array
    {
        // only screenings that are scheduled for the selected movie and date, and still have seats available
        $startOfDay = $date . " 00:00:00";
        $endOfDay = (new DateTimeImmutable($date))->modify("+1 day")->format("Y-m-d H:i:s");
        $statement = $this->connection->prepare(
            "SELECT s.screening_id, s.movie_id, s.hall_id, s.start_time, s.end_time,
                    s.screening_type, h.hall_name,
                    (
                        -- count active seats that do not have confirmed booking or live hold
                        SELECT COUNT(*)
                        FROM seats AS seat
                        WHERE seat.hall_id = s.hall_id
                          AND seat.is_active = 1
                          AND NOT EXISTS (
                              SELECT 1
                              FROM booking_seats AS booked_seat
                              INNER JOIN bookings AS booking
                                  ON booking.booking_id = booked_seat.booking_id
                              WHERE booked_seat.screening_id = s.screening_id
                                AND booked_seat.seat_id = seat.seat_id
                                AND booking.booking_status IN ('confirmed', 'completed')
                          )
                          AND NOT EXISTS (
                              SELECT 1
                              FROM active_seat_reservations AS reservation
                              WHERE reservation.screening_id = s.screening_id
                                AND reservation.seat_id = seat.seat_id
                                AND (
                                    reservation.expires_at IS NULL
                                    OR reservation.expires_at > CURRENT_TIMESTAMP
                                )
                          )
                    ) AS seats_left
             FROM screenings AS s
             INNER JOIN halls AS h ON h.hall_id = s.hall_id
             WHERE s.movie_id = ?
               AND s.status = 'scheduled'
               AND h.is_active = 1
               AND s.start_time >= ?
               AND s.start_time < ?
               AND s.start_time >= CURRENT_TIMESTAMP
             ORDER BY s.start_time"
        );
        $statement->bind_param("iss", $movieId, $startOfDay, $endOfDay);
        $statement->execute();
        $result = $statement->get_result();
        $screenings = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $screenings;
    }

    public function getScreeningCounts(int $movieId, string $startDate, string $endDate): array
    {
        // count future scheduled screenings for each date in the requested range
        $startOfRange = $startDate . " 00:00:00";
        $endOfRange = (new DateTimeImmutable($endDate))
            ->modify("+1 day")
            ->format("Y-m-d H:i:s");
        $statement = $this->connection->prepare(
            "SELECT DATE(s.start_time) AS screening_date, COUNT(*) AS screening_count
             FROM screenings AS s
             INNER JOIN halls AS h ON h.hall_id = s.hall_id
             WHERE s.movie_id = ?
               AND s.status = 'scheduled'
               AND h.is_active = 1
               AND s.start_time >= ?
               AND s.start_time < ?
               AND s.start_time >= CURRENT_TIMESTAMP
             GROUP BY DATE(s.start_time)"
        );
        $statement->bind_param("iss", $movieId, $startOfRange, $endOfRange);
        $statement->execute();
        $result = $statement->get_result();
        $counts = [];
        while ($row = $result->fetch_assoc()) {
            $counts[$row["screening_date"]] = (int) $row["screening_count"];
        }
        $statement->close();

        return $counts;
    }

    public function findScreening(int $screeningId, int $movieId, string $date): ?array
    {
        // find a specific screening by ID, movie, and date, ensuring it is still scheduled and has not started yet
        $startOfDay = $date . " 00:00:00";
        $endOfDay = (new DateTimeImmutable($date))->modify("+1 day")->format("Y-m-d H:i:s");
        $statement = $this->connection->prepare(
            "SELECT s.screening_id, s.movie_id, s.hall_id, s.start_time, s.end_time,
                    s.screening_type, h.hall_name, m.movie_title
             FROM screenings AS s
             INNER JOIN halls AS h ON h.hall_id = s.hall_id
             INNER JOIN MOVIES AS m ON m.movie_id = s.movie_id
             WHERE s.screening_id = ?
               AND s.movie_id = ?
               AND s.status = 'scheduled'
               AND h.is_active = 1
               AND s.start_time >= ?
               AND s.start_time < ?
               AND s.start_time >= CURRENT_TIMESTAMP
             LIMIT 1"
        );
        $statement->bind_param("iiss", $screeningId, $movieId, $startOfDay, $endOfDay);
        $statement->execute();
        $screening = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $screening;
    }

    public function getTicketPrices(int $screeningId): array
    {
        $statement = $this->connection->prepare(
            "SELECT tt.ticket_type_id, tt.ticket_type_name, tt.min_age, stp.ticket_price
             FROM screening_ticket_prices AS stp
             INNER JOIN ticket_types AS tt ON tt.ticket_type_id = stp.ticket_type_id
             WHERE stp.screening_id = ?
             ORDER BY tt.ticket_type_id"
        );
        $statement->bind_param("i", $screeningId);
        $statement->execute();
        $result = $statement->get_result();
        $prices = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $prices;
    }

    public function getSeats(int $screeningId, int $hallId): array
    {
        // combine the hall layout with confirmed seats and unexpired temporary reservations
        $statement = $this->connection->prepare(
            "SELECT seat.seat_id, seat.`row_number`, seat.seat_number, seat.seat_type,
                    CASE
                        WHEN booking.booking_id IS NOT NULL OR reservation.booking_id IS NOT NULL THEN 1
                        ELSE 0
                    END AS is_occupied
             FROM seats AS seat
             LEFT JOIN booking_seats AS booked_seat
               ON booked_seat.seat_id = seat.seat_id
              AND booked_seat.screening_id = ?
             LEFT JOIN bookings AS booking
               ON booking.booking_id = booked_seat.booking_id
              AND booking.booking_status IN ('confirmed', 'completed')
             LEFT JOIN active_seat_reservations AS reservation
               ON reservation.seat_id = seat.seat_id
              AND reservation.screening_id = ?
              AND (reservation.expires_at IS NULL OR reservation.expires_at > CURRENT_TIMESTAMP)
             WHERE seat.hall_id = ?
               AND seat.is_active = 1
             ORDER BY seat.`row_number`, seat.seat_number"
        );
        $statement->bind_param("iii", $screeningId, $screeningId, $hallId);
        $statement->execute();
        // keep the seat ordering consistent with the physical hall layout
        $result = $statement->get_result();
        $seats = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $seats;
    }
}
