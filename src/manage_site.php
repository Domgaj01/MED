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
        <p class="mt-2 text-sm text-white/60">Update the company information, contact details, and opening hours shown throughout the website.</p>
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

</main>

<?php include __DIR__ . "/footer.php"; ?>
