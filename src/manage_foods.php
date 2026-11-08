<?php

require_once __DIR__ . "/parts/admin_auth.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/FoodAdminController.php";

$page = new FoodAdminController(
    $conn,
    $_GET,
    $_POST,
    $_SERVER["REQUEST_METHOD"]
);

$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$isCreating = $page->isCreating;
$selectedProductId = $page->selectedProductId;
$product = $page->product;
$products = $page->products;
?>

<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold">Manage Food & Drinks</h1>
    <p class="mt-2 text-sm text-white/60">
        Create, edit and manage cinema food and drink products.
    </p>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status">
            <?= AdminPageController::escape($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert">
            <?= AdminPageController::escape($error) ?>
        </p>
    <?php endif; ?>

    <a
        class="mt-8 inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark"
        href="manage_foods.php?create=1"
    >
        Create product
    </a>

    <div class="relative mt-8">
        <label class="block text-sm font-medium" for="food-admin-search">
            Choose a product
            <input
                class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                id="food-admin-search"
                type="search"
                placeholder="Start typing a product name..."
                autocomplete="off"
            >
        </label>

        <div
            id="food-recommendations"
            class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-white/15 bg-[#1a1a1a] shadow-xl"
        ></div>

        <p class="mt-2 text-xs text-white/50" id="food-search-status" aria-live="polite">
            Type at least one letter to see matching products.
        </p>

        <div id="food-search-data" class="hidden" aria-hidden="true">
            <?php foreach ($products as $productOption): ?>
                <span
                    data-id="<?= (int) $productOption["product_id"] ?>"
                    data-title="<?= AdminPageController::escape($productOption["title"]) ?>"
                ></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($product !== null): ?>

        <form
            action="manage_foods.php<?= $isCreating ? '?create=1' : '?id=' . (int) $selectedProductId ?>"
            method="post"
            class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6"
        >
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? 'create' : 'update' ?>">

            <?php if (!$isCreating): ?>
                <input type="hidden" name="product_id" value="<?= (int) $selectedProductId ?>">
            <?php endif; ?>

            <label class="block text-sm font-medium">
                Product name
                <input
                    class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                    name="title"
                    maxlength="255"
                    value="<?= AdminPageController::escape($product["title"]) ?>"
                    required
                >
            </label>

            <label class="block text-sm font-medium">
                Description
                <textarea
                    class="mt-2 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 py-3 text-background"
                    name="description"
                    rows="3"
                ><?= AdminPageController::escape($product["description"] ?? "") ?></textarea>
            </label>

            <label class="block text-sm font-medium">
                Image URL
                <input
                    class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                    name="image_url"
                    type="url"
                    value="<?= AdminPageController::escape($product["image_url"] ?? "") ?>"
                >
            </label>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium">
                    Price
                    <input
                        class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                        name="price"
                        type="number"
                        min="0"
                        step="0.01"
                        value="<?= AdminPageController::escape((string) $product["price"]) ?>"
                        required
                    >
                </label>

                <label class="block text-sm font-medium">
                    Category
                    <select
                        class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                        name="category"
                        required
                    >
                        <?php foreach (["popcorn", "snacks", "drinks"] as $category): ?>
                            <option
                                value="<?= $category ?>"
                                <?= $product["category"] === $category ? "selected" : "" ?>
                            >
                                <?= ucfirst($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block text-sm font-medium">
                    Display order
                    <input
                        class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                        name="display_order"
                        type="number"
                        min="0"
                        value="<?= AdminPageController::escape((string) $product["display_order"]) ?>"
                        required
                    >
                </label>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="is_available"
                    value="1"
                    <?= (int) $product["is_available"] === 1 ? "checked" : "" ?>
                >
                Product available
            </label>

            <div class="flex flex-wrap gap-3">
                <button
                    class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark"
                    type="submit"
                >
                    <?= $isCreating ? "Create product" : "Save product" ?>
                </button>

                <?php if (!$isCreating): ?>
                    <button
                        class="rounded-lg border border-red-400/40 px-6 py-3 text-sm font-bold text-red-200 transition-colors hover:bg-red-400/10"
                        id="open-delete-dialog"
                        type="button"
                    >
                        Delete product
                    </button>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$isCreating): ?>
            <dialog
                class="fixed left-1/2 top-1/2 m-0 w-[min(100%-2rem,30rem)] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/15 bg-[#171717] p-0 text-background backdrop:bg-black/70"
                id="delete-dialog"
            >
                <div class="p-6">
                    <h2 class="text-xl font-bold">Delete this product?</h2>
                    <p class="mt-3 text-sm leading-6 text-white/65">
                        This will permanently delete
                        “<?= AdminPageController::escape($product["title"]) ?>”.
                        This action cannot be undone.
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-semibold hover:bg-white/5"
                            id="cancel-delete"
                            type="button"
                        >
                            Cancel
                        </button>

                        <button
                            class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600"
                            type="submit"
                            form="delete-product-form"
                        >
                            Delete product
                        </button>
                    </div>
                </div>
            </dialog>

            <form
                action="manage_foods.php?id=<?= (int) $selectedProductId ?>"
                id="delete-product-form"
                method="post"
                class="hidden"
            >
                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?= (int) $selectedProductId ?>">
            </form>
        <?php endif; ?>

    <?php elseif ($products === [] && !$isCreating): ?>
        <p class="mt-8 rounded-xl border border-white/10 p-5 text-sm text-white/60">
            No food or drink products are in the database yet.
        </p>
    <?php endif; ?>
</main>

<script>
    const foodSearch = document.getElementById("food-admin-search");
    const recommendations = document.getElementById("food-recommendations");
    const searchStatus = document.getElementById("food-search-status");

    const products = Array.from(
        document.querySelectorAll("#food-search-data [data-id]"),
        (item) => ({
            id: item.dataset.id,
            title: item.dataset.title
        })
    );

    if (foodSearch && recommendations && searchStatus) {
        foodSearch.addEventListener("input", () => {
            const query = foodSearch.value.trim().toLocaleLowerCase();
            recommendations.replaceChildren();

            if (query.length === 0) {
                recommendations.classList.add("hidden");
                searchStatus.textContent = "Type at least one letter to see matching products.";
                return;
            }

            const matches = products
                .filter((product) => product.title.toLocaleLowerCase().includes(query))
                .slice(0, 8);

            if (matches.length === 0) {
                recommendations.classList.add("hidden");
                searchStatus.textContent = "No matching products.";
                return;
            }

            for (const product of matches) {
                const option = document.createElement("a");
                option.className = "block px-4 py-3 text-sm text-background hover:bg-white/10";
                option.href = `manage_foods.php?id=${encodeURIComponent(product.id)}`;
                option.textContent = product.title;
                recommendations.append(option);
            }

            recommendations.classList.remove("hidden");
            searchStatus.textContent = `${matches.length} matching product(s). Choose one to open it.`;
        });

        document.addEventListener("click", (event) => {
            if (!event.target.closest("#food-admin-search, #food-recommendations")) {
                recommendations.classList.add("hidden");
            }
        });
    }

    const deleteDialog = document.getElementById("delete-dialog");
    const openDeleteDialog = document.getElementById("open-delete-dialog");
    const cancelDelete = document.getElementById("cancel-delete");

    if (deleteDialog && openDeleteDialog && cancelDelete) {
        openDeleteDialog.addEventListener("click", () => deleteDialog.showModal());
        cancelDelete.addEventListener("click", () => deleteDialog.close());

        deleteDialog.addEventListener("click", (event) => {
            if (event.target === deleteDialog) {
                deleteDialog.close();
            }
        });
    }
</script>

<?php include __DIR__ . "/footer.php"; ?>
