CREATE TABLE GENRES (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE MOVIES (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_title VARCHAR(255) NOT NULL,
    movie_description TEXT NULL,
    movie_duration INT NOT NULL,
    movie_release_date DATE NOT NULL,
    movie_age_rating VARCHAR(10) NOT NULL
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