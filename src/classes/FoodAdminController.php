<?php

require_once __DIR__ . "/AdminPageController.php";
require_once __DIR__ . "/FoodProductManager.php";

class FoodAdminController extends AdminPageController
{
    public bool $isCreating;
    public ?int $selectedProductId;
    public ?array $product = null;
    public array $products = [];

    private FoodProductManager $productManager;

    private array $categories = ["popcorn", "snacks", "drinks"];

    public function __construct(
        mysqli $connection,
        array $query,
        array $post,
        string $requestMethod
    ) {
        parent::__construct($connection);

        $this->productManager = new FoodProductManager($connection);
        $this->isCreating = ($query["create"] ?? "") === "1";
        $this->selectedProductId = $this->positiveInteger($query["id"] ?? null);

        if ($requestMethod === "POST") {
            $this->handlePost($post);
        }

        $this->setSuccessMessage($query);
        $this->loadSelectedProduct();
        $this->products = $this->productManager->getAll();
    }

    private function handlePost(array $post): void
    {
        if (!$this->verifyCsrfToken($post)) {
            return;
        }

        $action = isset($post["action"]) && is_string($post["action"])
            ? $post["action"]
            : "";

        if ($action === "delete") {
            $this->deleteProduct($post);
            return;
        }

        if ($action !== "create" && $action !== "update") {
            http_response_code(400);
            $this->error = "Invalid product action.";
            return;
        }

        $this->isCreating = $action === "create";
        $this->selectedProductId = $this->isCreating
            ? null
            : $this->positiveInteger($post["product_id"] ?? null);

        $product = [
            "title" => $this->postedString($post, "title"),
            "description" => $this->postedString($post, "description"),
            "image_url" => $this->postedString($post, "image_url"),
            "price" => $this->postedString($post, "price"),
            "category" => $this->postedString($post, "category"),
            "is_available" => isset($post["is_available"]) ? 1 : 0,
            "display_order" => $this->postedString($post, "display_order"),
        ];

        $this->product = $product;

        $price = filter_var($product["price"], FILTER_VALIDATE_FLOAT);
        $displayOrder = filter_var($product["display_order"], FILTER_VALIDATE_INT);

        if (!$this->isCreating && $this->selectedProductId === null) {
            $this->error = "Select a valid product.";
        } elseif ($product["title"] === "" || mb_strlen($product["title"], "UTF-8") > 255) {
            $this->error = "Product name is required and must not exceed 255 characters.";
        } elseif ($price === false || $price < 0) {
            $this->error = "Enter a valid product price.";
        } elseif (!in_array($product["category"], $this->categories, true)) {
            $this->error = "Choose a valid product category.";
        } elseif ($displayOrder === false || $displayOrder < 0) {
            $this->error = "Display order must be zero or a positive number.";
        } elseif ($product["image_url"] !== "" && filter_var($product["image_url"], FILTER_VALIDATE_URL) === false) {
            $this->error = "Enter a valid image URL.";
        } else {
            try {
                $product["price"] = (float) $price;
                $product["display_order"] = $displayOrder;

                $this->selectedProductId = $this->productManager->save(
                    $product,
                    $this->selectedProductId
                );

                $parameter = $this->isCreating ? "created=1" : "saved=1";

                $this->redirectTo(
                    "manage_foods.php?id=" . $this->selectedProductId . "&" . $parameter
                );
            } catch (mysqli_sql_exception $exception) {
                error_log("Food product save failed: " . $exception->getMessage());
                $this->error = "Could not save the product. Check the server log for details.";
            }
        }
    }

    private function deleteProduct(array $post): void
    {
        $productId = $this->positiveInteger($post["product_id"] ?? null);

        if ($productId === null) {
            $this->error = "Select a valid product to delete.";
            return;
        }

        try {
            if ($this->productManager->delete($productId)) {
                $this->redirectTo("manage_foods.php?deleted=1");
            }

            $this->error = "That product could not be found.";
        } catch (mysqli_sql_exception $exception) {
            error_log("Food product deletion failed: " . $exception->getMessage());
            $this->error = "Could not delete the product. Check the server log for details.";
        }
    }

    private function postedString(array $post, string $key): string
    {
        return isset($post[$key]) && is_string($post[$key])
            ? trim($post[$key])
            : "";
    }

    private function setSuccessMessage(array $query): void
    {
        if (isset($query["created"])) {
            $this->message = "Product created.";
        } elseif (isset($query["saved"])) {
            $this->message = "Product changes saved.";
        } elseif (isset($query["deleted"])) {
            $this->message = "Product deleted.";
        }
    }

    private function loadSelectedProduct(): void
    {
        if ($this->product === null && $this->selectedProductId !== null) {
            $this->product = $this->productManager->findById($this->selectedProductId);

            if ($this->product === null && $this->error === "") {
                $this->error = "That product could not be found.";
            }
        }

        if ($this->isCreating && $this->product === null) {
            $this->product = [
                "title" => "",
                "description" => "",
                "image_url" => "",
                "price" => "",
                "category" => "popcorn",
                "is_available" => 1,
                "display_order" => 0,
            ];
        }
    }
}
