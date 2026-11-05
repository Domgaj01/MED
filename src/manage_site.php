<?php
require_once __DIR__ . "/parts/admin_auth.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/SiteContentAdminController.php";

$page = new SiteContentAdminController($conn, $_GET, $_POST, $_SERVER["REQUEST_METHOD"]);
$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$settings = $page->settings;
$openingHours = $page->openingHours;
$news = $page->news;
$selectedNews = $page->selectedNews;
$selectedNewsId = $page->selectedNewsId;
$isCreatingNews = $page->isCreatingNews;
$days = [
    1 => "Monday",
    2 => "Tuesday",
    3 => "Wednesday",
    4 => "Thursday",
    5 => "Friday",
    6 => "Saturday",
    7 => "Sunday",
];
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-5xl space-y-14 px-6 py-12">
    <div>
        <h1 class="text-3xl font-bold">Manage website content</h1>
        <p class="mt-2 text-sm text-white/60">Update company information and publish news. Movies and screenings are managed in their own admin pages.</p>
    </div>

    <?php if ($message !== ""): ?>
        <p class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= AdminPageController::escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= AdminPageController::escape($error) ?></p>
    <?php endif; ?>

    <section id="site-settings" aria-labelledby="site-settings-title">
        <h2 class="text-2xl font-bold" id="site-settings-title">Company information and opening hours</h2>
        <form action="manage_site.php#site-settings" method="post" class="mt-6 space-y-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="save_site_content">

            <label class="block text-sm font-medium" for="company-description">
                Company description
                <textarea class="mt-2 min-h-40 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="company-description" name="company_description" maxlength="10000" required><?= AdminPageController::escape($settings["company_description"]) ?></textarea>
            </label>

            <fieldset>
                <legend class="text-lg font-semibold">Contact information</legend>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium" for="contact-address">
                        Address
                        <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="contact-address" name="contact_address" maxlength="255" value="<?= AdminPageController::escape($settings["contact_address"]) ?>">
                    </label>
                    <label class="block text-sm font-medium" for="contact-phone">
                        Phone
                        <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="contact-phone" name="contact_phone" type="tel" maxlength="50" value="<?= AdminPageController::escape($settings["contact_phone"]) ?>">
                    </label>
                    <label class="block text-sm font-medium sm:col-span-2" for="contact-email">
                        Email
                        <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="contact-email" name="contact_email" type="email" maxlength="254" value="<?= AdminPageController::escape($settings["contact_email"]) ?>">
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-lg font-semibold">Opening hours</legend>
                <div class="mt-4 space-y-3">
                    <?php foreach ($days as $day => $dayLabel): ?>
                        <?php $hours = $openingHours[$day] ?? ["opens_at" => null, "closes_at" => null, "is_closed" => 1]; ?>
                        <div class="grid items-center gap-3 rounded-xl border border-white/10 p-4 sm:grid-cols-[minmax(6rem,1fr)_1fr_1fr_auto]">
                            <span class="text-sm font-semibold"><?= $dayLabel ?></span>
                            <label class="text-xs text-white/60" for="opens-<?= $day ?>">
                                Opens
                                <input class="mt-1 h-10 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-2 text-sm text-background" id="opens-<?= $day ?>" name="opening_hours[<?= $day ?>][opens_at]" type="time" value="<?= AdminPageController::escape($hours["opens_at"] !== null ? substr($hours["opens_at"], 0, 5) : "") ?>">
                            </label>
                            <label class="text-xs text-white/60" for="closes-<?= $day ?>">
                                Closes
                                <input class="mt-1 h-10 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-2 text-sm text-background" id="closes-<?= $day ?>" name="opening_hours[<?= $day ?>][closes_at]" type="time" value="<?= AdminPageController::escape($hours["closes_at"] !== null ? substr($hours["closes_at"], 0, 5) : "") ?>">
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="opening_hours[<?= $day ?>][is_closed]" value="1" <?= (int) $hours["is_closed"] === 1 ? "checked" : "" ?>>
                                Closed
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="mt-3 text-xs text-white/50">Different opening and closing times are allowed for each day, including overnight closing times.</p>
            </fieldset>

            <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit">Save company information</button>
        </form>
    </section>

    <section id="news" aria-labelledby="news-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold" id="news-title">News</h2>
                <p class="mt-2 text-sm text-white/60">Published articles appear on the home page. Drafts remain visible only here.</p>
            </div>
            <a class="inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" href="manage_site.php?create_news=1#news">Create news article</a>
        </div>

        <?php if ($selectedNews !== null): ?>
            <form action="manage_site.php#news" method="post" class="mt-6 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $isCreatingNews ? "create_news" : "update_news" ?>">
                <?php if (!$isCreatingNews): ?>
                    <input type="hidden" name="news_id" value="<?= (int) $selectedNewsId ?>">
                <?php endif; ?>

                <h3 class="text-lg font-semibold"><?= $isCreatingNews ? "Create article" : "Edit article" ?></h3>
                <label class="block text-sm font-medium" for="news-title">
                    Title
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="news-title" name="title" maxlength="200" value="<?= AdminPageController::escape($selectedNews["title"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="news-body">
                    Article text
                    <textarea class="mt-2 min-h-48 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="news-body" name="body" maxlength="60000" required><?= AdminPageController::escape($selectedNews["body"]) ?></textarea>
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" <?= (int) $selectedNews["is_published"] === 1 ? "checked" : "" ?>>
                    Publish on the home page
                </label>
                <div class="flex flex-wrap gap-3">
                    <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit"><?= $isCreatingNews ? "Create article" : "Save article" ?></button>
                    <a class="rounded-lg border border-white/15 px-6 py-3 text-sm font-semibold hover:bg-white/5" href="manage_site.php#news">Cancel</a>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($news === []): ?>
            <p class="mt-6 rounded-xl border border-white/10 p-5 text-sm text-white/60">No news articles have been created yet.</p>
        <?php else: ?>
            <ul class="mt-6 space-y-3">
                <?php foreach ($news as $article): ?>
                    <li class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold"><?= AdminPageController::escape($article["title"]) ?></h3>
                            <p class="mt-1 text-xs text-white/50">
                                <?= (int) $article["is_published"] === 1 ? "Published" : "Draft" ?>
                                · <?= AdminPageController::escape(date("d M Y", strtotime($article["created_at"]))) ?>
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <a class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/5" href="manage_site.php?news_id=<?= (int) $article["news_id"] ?>#news">Edit</a>
                            <form action="manage_site.php#news" method="post" onsubmit="return confirm('Delete this news article?');">
                                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_news">
                                <input type="hidden" name="news_id" value="<?= (int) $article["news_id"] ?>">
                                <button class="rounded-lg border border-red-400/40 px-4 py-2 text-sm font-semibold text-red-200 hover:bg-red-400/10" type="submit">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . "/footer.php"; ?>
