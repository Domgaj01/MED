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