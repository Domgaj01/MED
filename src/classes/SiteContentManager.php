<?php

class SiteContentManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getSettings(): array
    {
        $result = $this->connection->query(
            "SELECT company_description, contact_address, contact_phone, contact_email
             FROM site_settings
             WHERE settings_id = 1"
        );
        $settings = $result->fetch_assoc();
        $result->free();

        if ($settings === null) {
            throw new RuntimeException("Site settings have not been initialized.");
        }

        return $settings;
    }

    public function getOpeningHours(): array
    {
        $result = $this->connection->query(
            "SELECT day_of_week, opens_at, closes_at, is_closed
             FROM site_opening_hours
             ORDER BY day_of_week"
        );
        $openingHours = [];
        while ($row = $result->fetch_assoc()) {
            $openingHours[(int) $row["day_of_week"]] = $row;
        }
        $result->free();

        return $openingHours;
    }

    public function saveSiteContent(array $settings, array $openingHours): void
    {
        $this->connection->begin_transaction();

        try {
            $settingsStatement = $this->connection->prepare(
                "UPDATE site_settings
                 SET company_description = ?, contact_address = ?, contact_phone = ?, contact_email = ?
                 WHERE settings_id = 1"
            );
            $settingsStatement->bind_param(
                "ssss",
                $settings["company_description"],
                $settings["contact_address"],
                $settings["contact_phone"],
                $settings["contact_email"]
            );
            $settingsStatement->execute();
            $settingsStatement->close();

            $hoursStatement = $this->connection->prepare(
                "UPDATE site_opening_hours
                 SET opens_at = ?, closes_at = ?, is_closed = ?
                 WHERE day_of_week = ?"
            );

            foreach ($openingHours as $day => $hours) {
                $opensAt = $hours["is_closed"] ? null : $hours["opens_at"] . ":00";
                $closesAt = $hours["is_closed"] ? null : $hours["closes_at"] . ":00";
                $isClosed = (int) $hours["is_closed"];
                $hoursStatement->bind_param("ssii", $opensAt, $closesAt, $isClosed, $day);
                $hoursStatement->execute();
            }

            $hoursStatement->close();
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollback();
            throw $exception;
        }
    }

    public function getNews(): array
    {
        $result = $this->connection->query(
            "SELECT news_id, title, body, is_published, created_at, updated_at
             FROM news_articles
             ORDER BY created_at DESC, news_id DESC"
        );
        $articles = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $articles;
    }

    public function getPublishedNews(int $limit = 3): array
    {
        $statement = $this->connection->prepare(
            "SELECT news_id, title, body, created_at
             FROM news_articles
             WHERE is_published = 1
             ORDER BY created_at DESC, news_id DESC
             LIMIT ?"
        );
        $statement->bind_param("i", $limit);
        $statement->execute();
        $articles = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $articles;
    }

    public function findNewsById(int $newsId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT news_id, title, body, is_published
             FROM news_articles
             WHERE news_id = ?"
        );
        $statement->bind_param("i", $newsId);
        $statement->execute();
        $article = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $article;
    }

    public function saveNews(array $article, ?int $newsId = null): int
    {
        if ($newsId === null) {
            $statement = $this->connection->prepare(
                "INSERT INTO news_articles (title, body, is_published)
                 VALUES (?, ?, ?)"
            );
            $statement->bind_param("ssi", $article["title"], $article["body"], $article["is_published"]);
            $statement->execute();
            $newsId = (int) $this->connection->insert_id;
            $statement->close();

            return $newsId;
        }

        if ($this->findNewsById($newsId) === null) {
            throw new DomainException("That news article could not be found.");
        }

        $statement = $this->connection->prepare(
            "UPDATE news_articles
             SET title = ?, body = ?, is_published = ?
             WHERE news_id = ?"
        );
        $statement->bind_param(
            "ssii",
            $article["title"],
            $article["body"],
            $article["is_published"],
            $newsId
        );
        $statement->execute();
        $statement->close();

        return $newsId;
    }

    public function deleteNews(int $newsId): bool
    {
        $statement = $this->connection->prepare(
            "DELETE FROM news_articles WHERE news_id = ?"
        );
        $statement->bind_param("i", $newsId);
        $statement->execute();
        $deleted = $statement->affected_rows === 1;
        $statement->close();

        return $deleted;
    }

    public function getTodayScreenings(): array
    {
        $result = $this->connection->query(
            "SELECT screening.screening_id, screening.start_time, screening.screening_type,
                    movie.movie_id, movie.movie_title, movie.movie_age_rating,
                    hall.hall_name
             FROM screenings AS screening
             INNER JOIN MOVIES AS movie ON movie.movie_id = screening.movie_id
             INNER JOIN halls AS hall ON hall.hall_id = screening.hall_id
             WHERE screening.status = 'scheduled'
               AND movie.movie_is_available = 1
               AND hall.is_active = 1
               AND screening.start_time >= CURRENT_DATE
               AND screening.start_time < CURRENT_DATE + INTERVAL 1 DAY
               AND screening.start_time >= CURRENT_TIMESTAMP
             ORDER BY screening.start_time
             LIMIT 12"
        );
        $screenings = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $screenings;
    }
}
