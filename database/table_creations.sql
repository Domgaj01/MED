CREATE TABLE GENRES (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL UNIQUE,
    genre_description TEXT NULL
);

CREATE TABLE MOVIES (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_title VARCHAR(255) NOT NULL,
    movie_description TEXT NULL,
    movie_duration INT NOT NULL,
    movie_release_date DATE NOT NULL,
    movie_age_rating VARCHAR(10) NOT NULL,
    movie_language VARCHAR(50) NOT NULL,
    movie_poster_url VARCHAR(255) NULL,
    movie_is_available BOOLEAN NOT NULL DEFAULT TRUE,
    movie_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE MOVIE_GENRES (
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    
    CONSTRAINT fk_movie_id FOREIGN KEY (movie_id) 
        REFERENCES MOVIES(movie_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
    CONSTRAINT fk_genre_id FOREIGN KEY (genre_id) 
        REFERENCES GENRES(genre_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE halls (
    hall_id INT AUTO_INCREMENT PRIMARY KEY,
    hall_name VARCHAR(50) NOT NULL UNIQUE,
    is_mystery_hall BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE seats (
    seat_id INT AUTO_INCREMENT PRIMARY KEY,
    hall_id INT NOT NULL,
    `row_number` VARCHAR(5) NOT NULL,
    seat_number INT NOT NULL,
    seat_type VARCHAR(20) NOT NULL DEFAULT 'standard',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uq_seats_hall_row_number (hall_id, `row_number`, seat_number),
    UNIQUE KEY uq_seats_seat_hall (seat_id, hall_id),
    CONSTRAINT fk_seats_hall
        FOREIGN KEY (hall_id) REFERENCES halls(hall_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE screenings (
    screening_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NULL,
    hall_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    screening_type VARCHAR(20) NOT NULL DEFAULT 'regular',
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_screenings_screening_hall (screening_id, hall_id),
    INDEX idx_screenings_status_start (status, start_time),
    INDEX idx_screenings_hall_time (hall_id, start_time, end_time),
    INDEX idx_screenings_movie_time (movie_id, start_time),
    CONSTRAINT fk_screenings_movie
        FOREIGN KEY (movie_id) REFERENCES MOVIES(movie_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_screenings_hall
        FOREIGN KEY (hall_id) REFERENCES halls(hall_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE ticket_types (
    ticket_type_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_type_name VARCHAR(50) NOT NULL UNIQUE,
    min_age INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE screening_ticket_prices (
    screening_ticket_price_id INT AUTO_INCREMENT PRIMARY KEY,
    screening_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    ticket_price DECIMAL(8,2) NOT NULL,
    UNIQUE KEY uq_screening_ticket_type (screening_id, ticket_type_id),
    CONSTRAINT fk_screening_ticket_prices_screening
        FOREIGN KEY (screening_id) REFERENCES screenings(screening_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_screening_ticket_prices_type
        FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(ticket_type_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    screening_id INT NOT NULL,
    booking_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    booking_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(8,2) NOT NULL,
    confirmed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    UNIQUE KEY uq_bookings_id_screening (booking_id, screening_id),
    INDEX idx_bookings_customer_date (customer_id, booking_date),
    INDEX idx_bookings_screening_status (screening_id, booking_status),
    CONSTRAINT fk_bookings_customer
        FOREIGN KEY (customer_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_screening
        FOREIGN KEY (screening_id) REFERENCES screenings(screening_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE booking_seats (
    booking_seat_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    screening_id INT NOT NULL,
    hall_id INT NOT NULL,
    seat_id INT NOT NULL,
    ticket_type_id INT NOT NULL,
    seat_price DECIMAL(8,2) NOT NULL,
    UNIQUE KEY uq_booking_seats_booking_seat (booking_id, seat_id),
    UNIQUE KEY uq_booking_seats_screening_seat (screening_id, seat_id),
    CONSTRAINT fk_booking_seats_booking_screening
        FOREIGN KEY (booking_id, screening_id)
        REFERENCES bookings(booking_id, screening_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_seats_screening_hall
        FOREIGN KEY (screening_id, hall_id)
        REFERENCES screenings(screening_id, hall_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_seats_seat_hall
        FOREIGN KEY (seat_id, hall_id)
        REFERENCES seats(seat_id, hall_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_booking_seats_ticket_type
        FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(ticket_type_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE active_seat_reservations (
    screening_id INT NOT NULL,
    hall_id INT NOT NULL,
    seat_id INT NOT NULL,
    booking_id INT NOT NULL,
    reserved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    PRIMARY KEY (screening_id, seat_id),
    UNIQUE KEY uq_active_reservations_booking_seat (booking_id, seat_id),
    INDEX idx_active_reservations_booking (booking_id),
    INDEX idx_active_reservations_expiry (expires_at),
    CONSTRAINT fk_active_reservations_booking_screening
        FOREIGN KEY (booking_id, screening_id)
        REFERENCES bookings(booking_id, screening_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_active_reservations_screening_hall
        FOREIGN KEY (screening_id, hall_id)
        REFERENCES screenings(screening_id, hall_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_active_reservations_seat_hall
        FOREIGN KEY (seat_id, hall_id)
        REFERENCES seats(seat_id, hall_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS food_drinks_products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    image_url VARCHAR(500) NULL,
    price DECIMAL(8,2) NOT NULL,
    category ENUM('popcorn', 'snacks', 'drinks') NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_food_drinks_category_available_order
        (category, is_available, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mystery_preferences (
    preference_id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,
    genre_id INT NOT NULL,

    favorite_movie_1_id INT NOT NULL,
    favorite_movie_2_id INT NOT NULL,
    favorite_movie_3_id INT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mystery_preferences_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(booking_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_mystery_preferences_genre
        FOREIGN KEY (genre_id)
        REFERENCES GENRES(genre_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_mystery_preferences_movie_1
        FOREIGN KEY (favorite_movie_1_id)
        REFERENCES MOVIES(movie_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_mystery_preferences_movie_2
        FOREIGN KEY (favorite_movie_2_id)
        REFERENCES MOVIES(movie_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_mystery_preferences_movie_3
        FOREIGN KEY (favorite_movie_3_id)
        REFERENCES MOVIES(movie_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    UNIQUE KEY uq_mystery_preferences_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mystery_preference_genres (
    preference_id INT NOT NULL,
    genre_id INT NOT NULL,

    PRIMARY KEY (preference_id, genre_id),

    FOREIGN KEY (preference_id)
        REFERENCES mystery_preferences(preference_id)
        ON DELETE CASCADE,

    FOREIGN KEY (genre_id)
        REFERENCES GENRES(genre_id)
        ON DELETE CASCADE
);

ALTER TABLE mystery_preferences
DROP FOREIGN KEY fk_mystery_preferences_genre;

ALTER TABLE mystery_preferences
DROP COLUMN genre_id;