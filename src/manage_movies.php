<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/MovieAdminController.php";

// Keep this file as the page entry point; the controller prepares state and handles requests.
$page = new MovieAdminController($conn, $_GET, $_POST, $_SERVER["REQUEST_METHOD"]);
$message = $page->message;
$error = $page->error;
$csrfToken = $page->csrfToken;
$isCreating = $page->isCreating;
$selectedMovieId = $page->selectedMovieId;
$movie = $page->movie;
$genres = $page->genres;
$movies = $page->movies;
$selectedGenreIds = $page->selectedGenreIds;
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold">Manage movies</h1>
    <p class="mt-2 text-sm text-white/60">Edit movie details and assign one or more genres.</p>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= AdminPageController::escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= AdminPageController::escape($error) ?></p>
    <?php endif; ?>

    <a class="mt-8 inline-flex rounded-lg bg-accent px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" href="manage_movies.php?create=1">Create movie</a>

    <div class="relative mt-8">
        <label class="block text-sm font-medium" for="movie-admin-search">
            Choose a movie
            <input
                class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background"
                id="movie-admin-search"
                type="search"
                placeholder="Start typing a movie title..."
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="movie-recommendations"
                aria-expanded="false"
                value="<?= $movie !== null ? AdminPageController::escape($movie["movie_title"]) : "" ?>"
            >
        </label>
        <div id="movie-recommendations" class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-white/15 bg-[#1a1a1a] shadow-xl" role="listbox"></div>
        <p class="mt-2 text-xs text-white/50" id="movie-search-status" aria-live="polite">Type at least one letter to see matching movies.</p>
        <!-- Hidden elements expose movie IDs and titles to JavaScript without another request. -->
        <div id="movie-search-data" class="hidden" aria-hidden="true">
            <?php foreach ($movies as $movieOption): ?>
                <span data-id="<?= (int) $movieOption["movie_id"] ?>" data-title="<?= AdminPageController::escape($movieOption["movie_title"]) ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($movie !== null): ?>
        <form action="manage_movies.php<?= $isCreating ? "?create=1" : "?id=" . (int) $selectedMovieId ?>" method="post" class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <!-- Hidden values tell the server which operation/movie this form represents. -->
            <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? "create" : "update" ?>">
            <?php if (!$isCreating): ?>
                <input type="hidden" name="movie_id" value="<?= (int) $selectedMovieId ?>">
            <?php endif; ?>

            <label class="block text-sm font-medium" for="movie-title">
                Title
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-title" name="movie_title" maxlength="255" value="<?= AdminPageController::escape($movie["movie_title"]) ?>" required>
            </label>

            <label class="block text-sm font-medium" for="movie-description">
                Description
                <textarea class="mt-2 min-h-28 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="movie-description" name="movie_description"><?= AdminPageController::escape($movie["movie_description"] ?? "") ?></textarea>
            </label>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium" for="movie-duration">
                    Duration (minutes)
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-duration" name="movie_duration" type="number" min="1" value="<?= AdminPageController::escape((string) $movie["movie_duration"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-age-rating">
                    Age rating
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-age-rating" name="movie_age_rating" maxlength="10" value="<?= AdminPageController::escape($movie["movie_age_rating"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-language">
                    Language
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-language" name="movie_language" maxlength="50" value="<?= AdminPageController::escape($movie["movie_language"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-release-date">
                    Release date
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-release-date" name="movie_release_date" type="date" value="<?= AdminPageController::escape($movie["movie_release_date"]) ?>" required>
                </label>
            </div>

            <label class="block text-sm font-medium" for="movie-poster-url">
                Poster URL (optional)
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-poster-url" name="movie_poster_url" type="url" maxlength="255" value="<?= AdminPageController::escape($movie["movie_poster_url"] ?? "") ?>">
            </label>

            <fieldset>
                <legend class="text-sm font-medium">Genres</legend>
                <?php if ($genres === []): ?>
                    <p class="mt-2 text-sm text-white/60">There are no genres yet. Add genres to the GENRES table first.</p>
                <?php else: ?>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <?php foreach ($genres as $genreId => $genreName): ?>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-sm">
                                <input type="checkbox" name="genre_ids[]" value="<?= $genreId ?>" <?= in_array($genreId, $selectedGenreIds, true) ? "checked" : "" ?>>
                                <?= AdminPageController::escape($genreName) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="movie_is_available" value="1" <?= (int) $movie["movie_is_available"] === 1 ? "checked" : "" ?>>
                Available to show
            </label>

            <div class="flex flex-wrap gap-3">
                <button class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-dark" type="submit"><?= $isCreating ? "Create movie" : "Save movie" ?></button>
                <?php if (!$isCreating): ?>
                    <button class="rounded-lg border border-red-400/40 px-6 py-3 text-sm font-bold text-red-200 transition-colors hover:bg-red-400/10" id="open-delete-movie-dialog" type="button">Delete movie</button>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!$isCreating): ?>
            <dialog class="fixed left-1/2 top-1/2 m-0 w-[min(100%-2rem,30rem)] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-white/15 bg-[#171717] p-0 text-background backdrop:bg-black/70" id="delete-movie-dialog" aria-labelledby="delete-movie-title" aria-describedby="delete-movie-description">
                <div class="p-6">
                    <h2 class="text-xl font-bold" id="delete-movie-title">Delete this movie?</h2>
                    <p class="mt-3 text-sm leading-6 text-white/65" id="delete-movie-description">
                        This will permanently delete “<?= AdminPageController::escape($movie["movie_title"]) ?>” and its genre assignments. This action cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-semibold hover:bg-white/5" id="cancel-delete-movie" type="button">Cancel</button>
                        <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600" type="submit" form="delete-movie-form">Delete movie</button>
                    </div>
                </div>
            </dialog>
            <form action="manage_movies.php?id=<?= (int) $selectedMovieId ?>" id="delete-movie-form" method="post" class="hidden">
                <input type="hidden" name="csrf_token" value="<?= AdminPageController::escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="movie_id" value="<?= (int) $selectedMovieId ?>">
            </form>
        <?php endif; ?>
    <?php elseif ($movies === [] && !$isCreating): ?>
        <p class="mt-8 rounded-xl border border-white/10 p-5 text-sm text-white/60">No movies are in the database yet.</p>
    <?php endif; ?>
</main>

<script>
    // js elements
    const movieAdminSearch = document.getElementById("movie-admin-search");
    const recommendations = document.getElementById("movie-recommendations");
    const searchStatus = document.getElementById("movie-search-status");
    const deleteDialog = document.getElementById("delete-movie-dialog");
    const openDeleteDialog = document.getElementById("open-delete-movie-dialog");
    const cancelDelete = document.getElementById("cancel-delete-movie");
    const movies = Array.from(document.querySelectorAll("#movie-search-data [data-id]"), (item) => ({
        id: item.dataset.id,
        title: item.dataset.title
    }));

    // refresh suggestions of movie names; selecting one goes to edit
    movieAdminSearch.addEventListener("input", () => {
        const query = movieAdminSearch.value.trim().toLocaleLowerCase();
        recommendations.replaceChildren();

        if (query.length === 0) {
            recommendations.classList.add("hidden");
            movieAdminSearch.setAttribute("aria-expanded", "false");
            searchStatus.textContent = "Type at least one letter to see matching movies.";
            return;
        }

        const matches = movies.filter((movie) => movie.title.toLocaleLowerCase().includes(query)).slice(0, 8);
        if (matches.length === 0) {
            recommendations.classList.add("hidden");
            movieAdminSearch.setAttribute("aria-expanded", "false");
            searchStatus.textContent = "No matching movies.";
            return;
        }

        for (const movie of matches) {
            const option = document.createElement("a");
            option.className = "block px-4 py-3 text-sm text-background hover:bg-white/10 focus:bg-white/10 focus:outline-none";
            option.href = `manage_movies.php?id=${encodeURIComponent(movie.id)}`;
            option.setAttribute("role", "option");
            option.textContent = movie.title;
            recommendations.append(option);
        }

        recommendations.classList.remove("hidden");
        movieAdminSearch.setAttribute("aria-expanded", "true");
        searchStatus.textContent = `${matches.length} matching movie${matches.length === 1 ? "" : "s"}. Choose one to open it.`;
    });

    // closing list of movies after selected
    document.addEventListener("click", (event) => {
        if (!event.target.closest("#movie-admin-search, #movie-recommendations")) {
            recommendations.classList.add("hidden");
            movieAdminSearch.setAttribute("aria-expanded", "false");
        }
    });

    // delete confirm
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