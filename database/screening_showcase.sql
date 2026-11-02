-- Run table_creations.sql first, then movies_showcase.sql, then this file.
-- These inserts are safe to run again: existing halls, seats, screenings, and prices are kept.

INSERT INTO ticket_types (ticket_type_name, min_age)
VALUES
    ('Adult', 18),
    ('Child', NULL),
    ('Senior', 65)
ON DUPLICATE KEY UPDATE min_age = VALUES(min_age);

INSERT INTO halls (hall_name, is_mystery_hall)
VALUES
    ('Hall 1', FALSE),
    ('Hall 2', FALSE),
    ('Hall 3', TRUE)
ON DUPLICATE KEY UPDATE hall_name = VALUES(hall_name);

-- Set up 8 rows of 12 seats in each showcase hall.
INSERT INTO seats (hall_id, `row_number`, seat_number)
SELECT hall.hall_id, seat_rows.row_number, seat_numbers.seat_number
FROM halls AS hall
CROSS JOIN (
    SELECT 'A' AS `row_number` UNION ALL SELECT 'B' UNION ALL
    SELECT 'C' UNION ALL SELECT 'D' UNION ALL
    SELECT 'E' UNION ALL SELECT 'F' UNION ALL
    SELECT 'G' UNION ALL SELECT 'H'
) AS seat_rows
CROSS JOIN (
    SELECT 1 AS seat_number UNION ALL SELECT 2 UNION ALL
    SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL
    SELECT 7 UNION ALL SELECT 8 UNION ALL
    SELECT 9 UNION ALL SELECT 10 UNION ALL
    SELECT 11 UNION ALL SELECT 12
) AS seat_numbers
WHERE hall.hall_name IN ('Hall 1', 'Hall 2', 'Hall 3')
  AND NOT EXISTS (
      SELECT 1
      FROM seats AS existing_seat
      WHERE existing_seat.hall_id = hall.hall_id
        AND existing_seat.`row_number` = seat_rows.row_number
        AND existing_seat.seat_number = seat_numbers.seat_number
  );

-- Fifteen screenings are spread over four dates, from three to six days ahead.
-- Each hall's showtimes leave enough time for the previous film to finish.
INSERT INTO screenings (
    movie_id,
    hall_id,
    start_time,
    end_time,
    screening_type,
    status
)
SELECT
    movie.movie_id,
    hall.hall_id,
    TIMESTAMP(
        DATE_ADD(CURRENT_DATE, INTERVAL showcase.day_offset DAY),
        showcase.show_time
    ),
    DATE_ADD(
        TIMESTAMP(
            DATE_ADD(CURRENT_DATE, INTERVAL showcase.day_offset DAY),
            showcase.show_time
        ),
        INTERVAL movie.movie_duration MINUTE
    ),
    'regular',
    'scheduled'
FROM (
    SELECT 3 AS day_offset, '13:00:00' AS show_time, 'Hall 1' AS hall_name, 'Inception' AS movie_title
    UNION ALL SELECT 3, '16:00:00', 'Hall 1', 'The Matrix'
    UNION ALL SELECT 3, '16:00:00', 'Hall 2', 'The Dark Knight'
    UNION ALL SELECT 3, '19:00:00', 'Hall 3', 'Interstellar'

    UNION ALL SELECT 4, '13:00:00', 'Hall 1', 'Avatar'
    UNION ALL SELECT 4, '13:00:00', 'Hall 2', 'Forrest Gump'
    UNION ALL SELECT 4, '16:00:00', 'Hall 2', 'Parasite'
    UNION ALL SELECT 4, '16:00:00', 'Hall 3', 'Gladiator'

    UNION ALL SELECT 5, '13:00:00', 'Hall 3', 'Spirited Away'
    UNION ALL SELECT 5, '16:00:00', 'Hall 1', 'Pulp Fiction'
    UNION ALL SELECT 5, '16:00:00', 'Hall 3', 'The Dark Knight'
    UNION ALL SELECT 5, '19:00:00', 'Hall 2', 'Inception'

    UNION ALL SELECT 6, '13:00:00', 'Hall 2', 'Avatar'
    UNION ALL SELECT 6, '16:00:00', 'Hall 3', 'Forrest Gump'
    UNION ALL SELECT 6, '19:00:00', 'Hall 1', 'Interstellar'
) AS showcase
INNER JOIN MOVIES AS movie
    ON movie.movie_title = showcase.movie_title
INNER JOIN halls AS hall
    ON hall.hall_name = showcase.hall_name
WHERE movie.movie_is_available = 1
  AND hall.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM screenings AS existing_screening
      WHERE existing_screening.movie_id = movie.movie_id
        AND existing_screening.hall_id = hall.hall_id
        AND existing_screening.start_time = TIMESTAMP(
            DATE_ADD(CURRENT_DATE, INTERVAL showcase.day_offset DAY),
            showcase.show_time
        )
  );

-- Set Adult/Child/Senior prices for each of the showcase screenings.
-- Child and Senior prices match the supplied design, and Adult is 115 DKK.
INSERT INTO screening_ticket_prices (screening_id, ticket_type_id, ticket_price)
SELECT
    screening.screening_id,
    ticket_type.ticket_type_id,
    CASE ticket_type.ticket_type_name
        WHEN 'Adult' THEN 115.00
        WHEN 'Child' THEN 75.00
        WHEN 'Senior' THEN 85.00
    END
FROM (
    SELECT 3 AS day_offset, '13:00:00' AS show_time, 'Hall 1' AS hall_name, 'Inception' AS movie_title
    UNION ALL SELECT 3, '16:00:00', 'Hall 1', 'The Matrix'
    UNION ALL SELECT 3, '16:00:00', 'Hall 2', 'The Dark Knight'
    UNION ALL SELECT 3, '19:00:00', 'Hall 3', 'Interstellar'

    UNION ALL SELECT 4, '13:00:00', 'Hall 1', 'Avatar'
    UNION ALL SELECT 4, '13:00:00', 'Hall 2', 'Forrest Gump'
    UNION ALL SELECT 4, '16:00:00', 'Hall 2', 'Parasite'
    UNION ALL SELECT 4, '16:00:00', 'Hall 3', 'Gladiator'

    UNION ALL SELECT 5, '13:00:00', 'Hall 3', 'Spirited Away'
    UNION ALL SELECT 5, '16:00:00', 'Hall 1', 'Pulp Fiction'
    UNION ALL SELECT 5, '16:00:00', 'Hall 3', 'The Dark Knight'
    UNION ALL SELECT 5, '19:00:00', 'Hall 2', 'Inception'

    UNION ALL SELECT 6, '13:00:00', 'Hall 2', 'Avatar'
    UNION ALL SELECT 6, '16:00:00', 'Hall 3', 'Forrest Gump'
    UNION ALL SELECT 6, '19:00:00', 'Hall 1', 'Interstellar'
) AS showcase
INNER JOIN MOVIES AS movie
    ON movie.movie_title = showcase.movie_title
INNER JOIN halls AS hall
    ON hall.hall_name = showcase.hall_name
INNER JOIN screenings AS screening
    ON screening.movie_id = movie.movie_id
   AND screening.hall_id = hall.hall_id
   AND screening.start_time = TIMESTAMP(
       DATE_ADD(CURRENT_DATE, INTERVAL showcase.day_offset DAY),
       showcase.show_time
   )
INNER JOIN ticket_types AS ticket_type
    ON ticket_type.ticket_type_name IN ('Adult', 'Child', 'Senior')
WHERE NOT EXISTS (
    SELECT 1
    FROM screening_ticket_prices AS existing_price
    WHERE existing_price.screening_id = screening.screening_id
      AND existing_price.ticket_type_id = ticket_type.ticket_type_id
);
