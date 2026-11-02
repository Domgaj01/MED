
<?php
require_once __DIR__ . "/./parts/db.php";
require_once __DIR__ . "/./parts/session.php";

/**
 * Product class
 * Handles all database operations for food and drinks.
 */
class Product
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getProductsByCategory(): array
    {
        $sql = "
            SELECT
                product_id,
                title,
                description,
                image_url,
                price,
                category,
                display_order
            FROM food_drinks_products
            WHERE is_available = 1
            ORDER BY
                FIELD(category, 'popcorn', 'snacks', 'drinks'),
                display_order ASC,
                title ASC
        ";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new RuntimeException(
                'Database query failed: ' . $this->conn->error
            );
        }

        $productsByCategory = [
            'popcorn' => [],
            'snacks' => [],
            'drinks' => []
        ];

        while ($row = $result->fetch_assoc()) {
            if (isset($productsByCategory[$row['category']])) {
                $productsByCategory[$row['category']][] = $row;
            }
        }

        $result->free();

        return $productsByCategory;
    }
}

/**
 * Prepare product image URL.
 */
function getProductImageUrl(?string $imageUrl): string
{
    $imageUrl = trim($imageUrl ?? '');

    if ($imageUrl === '') {
        return '';
    }

    if (
        preg_match('~^(https?:)?//~i', $imageUrl) ||
        str_starts_with($imageUrl, 'data:') ||
        str_starts_with($imageUrl, '/')
    ) {
        return $imageUrl;
    }

    return '/MED/' . ltrim($imageUrl, '/');
}

// Initialize variables
$databaseError = false;

$categories = [
    'popcorn' => [
        'name' => 'Popcorn',
        'description' => 'Classic salted, sweet, or caramel — the cinema essential.'
    ],
    'snacks' => [
        'name' => 'Snacks',
        'description' => 'Discover our selection of delicious cinema snacks.'
    ],
    'drinks' => [
        'name' => 'Drinks',
        'description' => 'Enjoy refreshing drinks while watching your favourite movie.'
    ]
];

$productsByCategory = [
    'popcorn' => [],
    'snacks' => [],
    'drinks' => []
];

// Load products from database
try {
    $productModel = new Product($conn);
    $productsByCategory = $productModel->getProductsByCategory();
} catch (Throwable $error) {
    error_log($error->getMessage());
    $databaseError = true;
}

include __DIR__ . '/./header.php';
?>

<!-- Food & Drinks intro - full width -->
<div class="movies-intro w-full border-b border-white/10 bg-[#181818] px-[clamp(24px,4vw,56px)] py-12 max-[480px]:py-9">

    <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-accent">
        At the counter
    </p>

    <h1 class="text-4xl font-black uppercase tracking-[-0.04em] max-[480px]:text-3xl">
        Food &amp; Drinks
    </h1>

    <p class="mt-2 text-sm text-[#a6a6a6]">
        A full selection of food and drinks is available at our counter.
        Purchase on arrival — no pre-ordering needed.
    </p>

</div>

<!-- Main content -->
<main class="min-h-screen bg-night px-4 pb-20 pt-10 font-lexend text-white sm:px-6 lg:px-8">

    <div class="mx-auto w-full max-w-[1440px]">

        <!-- Information banner -->
        <section class="mb-16 flex flex-col items-start justify-between gap-5 rounded-2xl border border-white/10 bg-[#191919] px-6 py-6 md:flex-row md:items-center">

            <div class="min-w-0">

                <h2 class="text-lg font-semibold text-[#f1f1f1]">
                    Available at the cinema
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-[#929292] sm:text-base">
                    All food and drinks are sold at our counter.
                    Arrive a few minutes early to pick up your order before the film starts.
                </p>

            </div>

            <a
                href="/MED/movies.php"
                class="inline-flex min-h-12 w-full shrink-0 items-center justify-center rounded-xl bg-accent px-7 py-3 text-sm font-bold text-white transition-colors duration-300 hover:bg-accent-dark md:w-auto"
            >
                Book a Movie
            </a>

        </section>

        <!-- Database error -->
        <?php if ($databaseError): ?>

            <div class="mb-10 rounded-xl border border-red-500/30 bg-red-500/10 p-5 text-sm text-red-200">
                Products could not be loaded. Please check your database connection.
            </div>

        <?php else: ?>

            <!-- Product categories -->
            <?php foreach ($categories as $categoryKey => $category): ?>

                <?php $categoryProducts = $productsByCategory[$categoryKey]; ?>

                <section class="mb-16 last:mb-0">

                    <!-- Category heading -->
                    <div class="mb-8">
                        <h2 class="mb-2 text-2xl font-bold tracking-tight text-[#f1f1f1] sm:text-3xl">
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <p class="text-sm leading-relaxed text-[#929292] sm:text-base">
                            <?= htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <?php if (!empty($categoryProducts)): ?>

                        <!-- Product grid -->
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3">

                            <?php foreach ($categoryProducts as $product): ?>

                                <article class="group overflow-hidden rounded-xl border border-white/10 bg-[#191919] transition-colors duration-300 hover:border-white/20">

                                    <!-- Product image -->
<div class="relative h-44 w-full overflow-hidden bg-[#242424]">

<?php
    $imageUrl = getProductImageUrl($product['image_url'] ?? '');
?>

<?php if ($imageUrl !== ''): ?>

    <img
        src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
        alt="<?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>"
        class="block h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        loading="lazy"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
    >

    <div class="hidden h-full w-full items-center justify-center text-sm text-[#929292]">
        Image unavailable
    </div>

<?php else: ?>

    <div class="flex h-full w-full items-center justify-center text-sm text-[#929292]">
        No Image
    </div>

<?php endif; ?>

</div>
                                    <!-- Product information -->
                                    <div class="p-4">

                                        <!-- Title and price -->
                                        <div class="mb-1 flex items-start justify-between gap-2">

                                            <h3 class="text-sm font-bold leading-tight text-[#f1f1f1]">
                                                <?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>
                                            </h3>

                                            <span class="shrink-0 text-sm font-bold text-accent">
                                                <?= number_format((float) $product['price'], 2, ',', ' ') ?> DKK
                                            </span>

                                        </div>

                                        <!-- Description -->
                                        <p class="text-xs leading-relaxed text-[#929292]">
                                            <?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </p>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p class="rounded-xl border border-dashed border-white/15 bg-[#171717] p-6 text-sm text-[#999999]">
                            No products available.
                        </p>

                    <?php endif; ?>

                </section>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<?php include __DIR__ . '/./footer.php'; ?>
