<?php
require_once __DIR__ . "/parts/admin_auth.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/ScreeningsAdminController.php";

$page = new ScreeningsAdminController($conn, $_GET, $_POST, $_SERVER["REQUEST_METHOD"]);
$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$isCreating = $page->isCreating;
$selectedScreeningId = $page->selectedScreeningId;
$screening = $page->screening;
$movies = $page->movies;
$halls = $page->halls;
$ticketTypes = $page->ticketTypes;
$prices = $page->prices;
$screenings = $page->screenings;
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-6xl px-6 py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Manage screenings</h1>
            <p class="mt-2 text-sm text-white/60">Schedule movies, choose halls, set ticket prices, and manage showtime status.</p>
        </div>
        <a class="inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" href="manage_screenings.php?create=1">Create screening</a>
    </div>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= AdminPageController::escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= AdminPageController::escape($error) ?></p>
    <?php endif; ?>

    <?php if ($screening !== null): ?>
        <form action="manage_screenings.php<?= $isCreating ? "?create=1" : "?id=" . (int) $selectedScreeningId ?>" method="post" class="mt-8 space-y-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? "create" : "update" ?>">
            <?php if (!$isCreating): ?>
                <input type="hidden" name="screening_id" value="<?= (int) $selectedScreeningId ?>">
            <?php endif; ?>

            <h2 class="text-xl font-bold"><?= $isCreating ? "Create screening" : "Edit screening" ?></h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium" for="screening-movie">
                    Movie
                    <select class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="screening-movie" name="movie_id" required>
                        <option value="">Choose a movie</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?= (int) $movie["movie_id"] ?>" <?= (int) ($screening["movie_id"] ?? 0) === (int) $movie["movie_id"] ? "selected" : "" ?>>
                                <?= AdminPageController::escape($movie["movie_title"]) ?><?= (int) $movie["movie_is_available"] === 1 ? "" : " (not currently available)" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block text-sm font-medium" for="screening-hall">
                    Hall
                    <select class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="screening-hall" name="hall_id" required>
                        <option value="">Choose a hall</option>
                        <?php foreach ($halls as $hall): ?>
                            <option value="<?= (int) $hall["hall_id"] ?>" <?= (int) ($screening["hall_id"] ?? 0) === (int) $hall["hall_id"] ? "selected" : "" ?>>
                                <?= AdminPageController::escape($hall["hall_name"]) ?><?= (int) $hall["is_active"] === 1 ? "" : " (inactive)" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block text-sm font-medium" for="screening-start">
                    Start date and time
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="screening-start" name="start_time" type="datetime-local" value="<?= AdminPageController::escape($screening["start_time_input"] ?? "") ?>" required>
                </label>

                <label class="block text-sm font-medium" for="screening-type">
                    Screening type
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="screening-type" name="screening_type" maxlength="20" value="<?= AdminPageController::escape($screening["screening_type"] ?? "regular") ?>" required>
                </label>

                <label class="block text-sm font-medium" for="screening-status">
                    Status
                    <select class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="screening-status" name="status" required>
                        <?php foreach (["scheduled" => "Scheduled", "cancelled" => "Cancelled", "completed" => "Completed"] as $statusValue => $statusLabel): ?>
                            <option value="<?= $statusValue ?>" <?= ($screening["status"] ?? "scheduled") === $statusValue ? "selected" : "" ?>><?= $statusLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <p class="text-xs leading-5 text-white/50">The end time is calculated from the selected movie’s runtime. Scheduled screenings cannot overlap in the same hall.</p>

            <fieldset>
                <legend class="text-sm font-medium">Ticket prices</legend>
                <?php if ($ticketTypes === []): ?>
                    <p class="mt-2 text-sm text-white/60">No ticket types are configured. Add ticket types before scheduling a screening.</p>
                <?php else: ?>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($ticketTypes as $ticketType): ?>
                            <?php $ticketTypeId = (int) $ticketType["ticket_type_id"]; ?>
                            <label class="block text-sm font-medium" for="ticket-price-<?= $ticketTypeId ?>">
                                <?= AdminPageController::escape($ticketType["ticket_type_name"]) ?> price
                                <div class="mt-2 flex items-center rounded-lg border border-white/15 bg-[#1a1a1a] px-3">
                                    <span class="text-white/50">kr</span>
                                    <input class="h-11 w-full bg-transparent px-2 text-background outline-none" id="ticket-price-<?= $ticketTypeId ?>" name="prices[<?= $ticketTypeId ?>]" type="number" min="0" max="999999.99" step="0.01" value="<?= AdminPageController::escape((string) ($prices[$ticketTypeId] ?? "")) ?>" required>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <div class="flex flex-wrap gap-3">
                <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit" <?= $movies === [] || $halls === [] || $ticketTypes === [] ? "disabled" : "" ?>><?= $isCreating ? "Create screening" : "Save screening" ?></button>
                <?php if (!$isCreating): ?>
                    <button class="rounded-lg border border-red-400/40 px-6 py-3 text-sm font-bold text-red-200 transition-colors hover:bg-red-400/10" id="open-delete-screening" type="button">Delete screening</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$isCreating): ?>
            <dialog class="fixed left-1/2 top-1/2 m-0 w-[min(100%-2rem,30rem)] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/15 bg-[#171717] p-0 text-background backdrop:bg-black/70" id="delete-screening-dialog" aria-labelledby="delete-screening-title">
                <div class="p-6">
                    <h2 class="text-xl font-bold" id="delete-screening-title">Delete this screening?</h2>
                    <p class="mt-3 text-sm leading-6 text-white/65">Screenings with bookings cannot be deleted. Cancel the screening instead to preserve its booking history.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-semibold hover:bg-white/5" id="cancel-delete-screening" type="button">Cancel</button>
                        <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600" type="submit" form="delete-screening-form">Delete screening</button>
                    </div>
                </div>
            </dialog>
            <form action="manage_screenings.php?id=<?= (int) $selectedScreeningId ?>" id="delete-screening-form" method="post" class="hidden">
                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="screening_id" value="<?= (int) $selectedScreeningId ?>">
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <section class="mt-10" aria-labelledby="screenings-list-title">
        <h2 class="text-xl font-bold" id="screenings-list-title">Screenings</h2>
        <?php if ($screenings === []): ?>
            <p class="mt-4 rounded-xl border border-white/10 p-5 text-sm text-white/60">No screenings are in the database yet.</p>
        <?php else: ?>
            <div class="mt-4 overflow-x-auto rounded-xl border border-white/10">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-white/[0.04] text-xs uppercase tracking-wide text-white/60">
                        <tr>
                            <th class="px-4 py-3 font-semibold" scope="col">Movie</th>
                            <th class="px-4 py-3 font-semibold" scope="col">Date and time</th>
                            <th class="px-4 py-3 font-semibold" scope="col">Hall</th>
                            <th class="px-4 py-3 font-semibold" scope="col">Type</th>
                            <th class="px-4 py-3 font-semibold" scope="col">Status</th>
                            <th class="px-4 py-3 text-right font-semibold" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php foreach ($screenings as $listedScreening): ?>
                            <tr>
                                <td class="px-4 py-4 font-medium"><?= AdminPageController::escape($listedScreening["movie_title"] ?? "Movie unavailable") ?></td>
                                <td class="whitespace-nowrap px-4 py-4 text-white/70"><?= AdminPageController::escape(date("d M Y, H:i", strtotime($listedScreening["start_time"]))) ?></td>
                                <td class="px-4 py-4 text-white/70"><?= AdminPageController::escape($listedScreening["hall_name"]) ?></td>
                                <td class="px-4 py-4 text-white/70"><?= AdminPageController::escape($listedScreening["screening_type"]) ?></td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $listedScreening["status"] === "scheduled" ? "bg-emerald-400/10 text-emerald-200" : "bg-white/10 text-white/70" ?>">
                                        <?= AdminPageController::escape(ucfirst($listedScreening["status"])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a class="rounded-lg border border-white/15 px-4 py-2 font-semibold hover:bg-white/5" href="manage_screenings.php?id=<?= (int) $listedScreening["screening_id"] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-white/50">Showing the latest 100 screenings.</p>
        <?php endif; ?>
    </section>
</main>

<script>
    const deleteScreeningDialog = document.getElementById("delete-screening-dialog");
    const openDeleteScreening = document.getElementById("open-delete-screening");
    const cancelDeleteScreening = document.getElementById("cancel-delete-screening");
    
    // delete confirm

    if (deleteScreeningDialog && openDeleteScreening && cancelDeleteScreening) {
        openDeleteScreening.addEventListener("click", () => deleteScreeningDialog.showModal());
        cancelDeleteScreening.addEventListener("click", () => deleteScreeningDialog.close());
        deleteScreeningDialog.addEventListener("click", (event) => {
            if (event.target === deleteScreeningDialog) {
                deleteScreeningDialog.close();
            }
        });
    }
</script>
<?php include __DIR__ . "/footer.php"; ?>