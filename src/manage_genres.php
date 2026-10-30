<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/GenreAdminController.php";

// Keep this file as the page entry point; the controller prepares state and handles requests.
$page = new GenreAdminController($conn, $_GET, $_POST, $_SERVER["REQUEST_METHOD"]);
$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$isCreating = $page->isCreating;
$genreId = $page->genreId;
$genre = $page->genre;
$genres = $page->genres;
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold">Manage genres</h1>
    <p class="mt-2 text-sm text-white/60">Create, edit, or remove movie genres.</p>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= AdminPageController::escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= AdminPageController::escape($error) ?></p>
    <?php endif; ?>

    <a class="mt-8 inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" href="manage_genres.php?create=1">Create genre</a>

    <?php if ($genre !== null): ?>
        <form action="manage_genres.php<?= $isCreating ? "?create=1" : "?id=" . (int) $genreId ?>" method="post" class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? "create" : "update" ?>">
            <?php if (!$isCreating): ?>
                <input type="hidden" name="genre_id" value="<?= (int) $genreId ?>">
            <?php endif; ?>

            <label class="block text-sm font-medium" for="genre-name">
                Genre name
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="genre-name" name="genre_name" maxlength="100" value="<?= AdminPageController::escape($genre["genre_name"]) ?>" required>
            </label>

            <label class="block text-sm font-medium" for="genre-description">
                Description (optional)
                <textarea class="mt-2 min-h-28 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="genre-description" name="genre_description"><?= AdminPageController::escape($genre["genre_description"] ?? "") ?></textarea>
            </label>

            <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit"><?= $isCreating ? "Create genre" : "Save genre" ?></button>
        </form>
    <?php endif; ?>

    <section class="mt-10" aria-labelledby="genres-list-title">
        <h2 class="text-xl font-bold" id="genres-list-title">Genres</h2>
        <?php if ($genres === []): ?>
            <p class="mt-4 rounded-xl border border-white/10 p-5 text-sm text-white/60">No genres are in the database yet.</p>
        <?php else: ?>
            <ul class="mt-4 space-y-3">
                <?php foreach ($genres as $listedGenre): ?>
                    <li class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold"><?= AdminPageController::escape($listedGenre["genre_name"]) ?></h3>
                            <?php if (!empty($listedGenre["genre_description"])): ?>
                                <p class="mt-1 text-sm text-white/60"><?= AdminPageController::escape($listedGenre["genre_description"]) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <a class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/5" href="manage_genres.php?id=<?= (int) $listedGenre["genre_id"] ?>">Edit</a>
                            <button
                                class="open-delete-genre-dialog rounded-lg border border-red-400/40 px-4 py-2 text-sm font-semibold text-red-200 hover:bg-red-400/10"
                                type="button"
                                data-genre-id="<?= (int) $listedGenre["genre_id"] ?>"
                                data-genre-name="<?= AdminPageController::escape($listedGenre["genre_name"]) ?>"
                            >Delete</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <dialog class="fixed left-1/2 top-1/2 m-0 w-[min(100%-2rem,30rem)] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/15 bg-[#171717] p-0 text-background backdrop:bg-black/70" id="delete-genre-dialog" aria-labelledby="delete-genre-title" aria-describedby="delete-genre-description">
        <div class="p-6">
            <h2 class="text-xl font-bold" id="delete-genre-title">Delete this genre?</h2>
            <p class="mt-3 text-sm leading-6 text-white/65" id="delete-genre-description">
                Deleting this genre removes its assignments from movies. Movies themselves will not be deleted.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-semibold hover:bg-white/5" id="cancel-delete-genre" type="button">Cancel</button>
                <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600" type="submit" form="delete-genre-form">Delete genre</button>
            </div>
        </div>
    </dialog>
    <form action="manage_genres.php" id="delete-genre-form" method="post" class="hidden">
        <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="genre_id" id="delete-genre-id">
    </form>
</main>

<script>
    const deleteGenreDialog = document.getElementById("delete-genre-dialog");
    const deleteGenreDescription = document.getElementById("delete-genre-description");
    const deleteGenreId = document.getElementById("delete-genre-id");
    const cancelDeleteGenre = document.getElementById("cancel-delete-genre");

    for (const button of document.querySelectorAll(".open-delete-genre-dialog")) {
        button.addEventListener("click", () => {
            deleteGenreId.value = button.dataset.genreId;
            deleteGenreDescription.textContent =
                `Deleting “${button.dataset.genreName}” removes its assignments from movies. Movies themselves will not be deleted.`;
            deleteGenreDialog.showModal();
        });
    }

    // canceling of dialog
    cancelDeleteGenre.addEventListener("click", () => deleteGenreDialog.close());
    deleteGenreDialog.addEventListener("click", (event) => {
        if (event.target === deleteGenreDialog) {
            deleteGenreDialog.close();
        }
    });
</script>
<?php include __DIR__ . "/footer.php"; ?>
