
<?php

class FoodProductManager
{
    private mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getAll(): array
    {
        $sql = "SELECT product_id, title, description, image_url, price, category,
                       is_available, display_order, created_at
                FROM food_drinks_products
                ORDER BY FIELD(category, 'popcorn', 'snacks', 'drinks'),
                         display_order ASC, title ASC";

        $result = $this->connection->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $productId): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT product_id, title, description, image_url, price, category,
                    is_available, display_order, created_at
             FROM food_drinks_products
             WHERE product_id = ?"
        );

        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $product ?: null;
    }

    public function save(array $product, ?int $productId = null): int
    {
        if ($productId === null) {
            $stmt = $this->connection->prepare(
                "INSERT INTO food_drinks_products
                    (title, description, image_url, price, category, is_available, display_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssdsii",
                $product["title"],
                $product["description"],
                $product["image_url"],
                $product["price"],
                $product["category"],
                $product["is_available"],
                $product["display_order"]
            );

            $stmt->execute();
            $newId = (int) $this->connection->insert_id;
            $stmt->close();

            return $newId;
        }

        $stmt = $this->connection->prepare(
            "UPDATE food_drinks_products
             SET title = ?, description = ?, image_url = ?, price = ?,
                 category = ?, is_available = ?, display_order = ?
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            "sssdsiii",
            $product["title"],
            $product["description"],
            $product["image_url"],
            $product["price"],
            $product["category"],
            $product["is_available"],
            $product["display_order"],
            $productId
        );

        $stmt->execute();
        $stmt->close();

        return $productId;
    }

    public function delete(int $productId): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM food_drinks_products WHERE product_id = ?"
        );

        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        return $deleted;
    }
}
