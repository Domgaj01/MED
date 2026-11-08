<?php

require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";

require_once __DIR__ . "/classes/AdminCsrfToken.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/MysteryGenreManager.php";
require_once __DIR__ . "/classes/MysteryMovieManager.php";
require_once __DIR__ . "/classes/MysteryCinemaController.php";

confirm_logged_in();

$page = new MysteryCinemaController(
    $conn,
    $_GET,
    $_POST,
    $_SERVER["REQUEST_METHOD"]
);

$genres = $page->getGenres();
$selectedGenreIds = $page->getSelectedGenreIds();
$movies = $page->getMovies();
$selectedMovieIds = $page->getSelectedMovieIds();
$csrfToken = $page->csrfToken;

$step = empty($selectedGenreIds) ? 1 : 2;

?>

<?php include __DIR__ . "/header.php"; ?>

<main class="min-h-[750px] bg-[#0d0d0d] px-6 py-12 text-white">

    <div class="mx-auto max-w-5xl">

        <!-- Progress steps -->
        <div class="mx-auto mb-16 flex max-w-3xl items-start justify-between">

            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-red-900 bg-red-700 font-bold">
                    1
                </div>
                <span class="mt-3 text-sm font-semibold">
                    Choose Genres
                </span>
            </div>

            <div class="mt-7 h-[3px] flex-1 <?= $step === 2 ? 'bg-red-700' : 'bg-white/15' ?>"></div>

            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full <?= $step === 2 ? 'border-4 border-red-900 bg-red-700' : 'bg-white/15 text-white/50' ?> font-bold">
                    2
                </div>
                <span class="mt-3 text-sm <?= $step === 2 ? 'font-semibold' : 'text-white/50' ?>">
                    Pick 3 Films
                </span>
            </div>

            <div class="mt-7 h-[3px] flex-1 bg-white/15"></div>

            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 font-bold text-white/50">
                    3
                </div>
                <span class="mt-3 text-sm text-white/50">
                    Create Match
                </span>
            </div>

        </div>

        <!-- Messages -->
        <?php if ($page->message !== ""): ?>
            <p class="mb-6 text-center text-sm text-green-400" role="status">
                <?= AdminPageController::escape($page->message) ?>
            </p>
        <?php endif; ?>

        <?php if ($page->error !== ""): ?>
            <p class="mb-6 text-center text-sm text-red-400" role="alert">
                <?= AdminPageController::escape($page->error) ?>
            </p>
        <?php endif; ?>


        <?php if ($step === 1): ?>

            <!-- STEP 1: GENRES -->

            <div class="mb-16 text-center">

                <h1 class="text-3xl font-bold md:text-4xl">
                    What's your favourite genres?
                </h1>

                <p class="mt-4 text-lg text-white/50">
                    Pick all the genres you enjoy.
                </p>

            </div>

            <form action="mystery.php" method="post" id="genre-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= AdminPageController::escape($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="select_genres"
                >

                <div class="mx-auto grid max-w-4xl grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4">

                    <?php foreach ($genres as $genre): ?>

                        <?php
                        $genreId = (int) $genre["genre_id"];
                        $isSelected = in_array($genreId, $selectedGenreIds, true);
                        ?>

                        <label class="cursor-pointer">

                            <input
                                type="checkbox"
                                name="genre_ids[]"
                                value="<?= $genreId ?>"
                                class="peer sr-only"
                                <?= $isSelected ? "checked" : "" ?>
                            >

                            <div class="flex min-h-[110px] items-center justify-center rounded-2xl border-[3px] border-[#292929] bg-[#1a1a1a] px-4 py-6 text-center text-lg font-bold transition-all hover:border-red-700 hover:bg-[#242424] peer-checked:border-red-600 peer-checked:bg-red-950/40">

                                <?= AdminPageController::escape($genre["genre_name"]) ?>

                            </div>

                        </label>

                    <?php endforeach; ?>

                </div>

                <div class="mt-16 flex justify-center">

                    <button
                        type="submit"
                        id="next-button"
                        class="min-w-[300px] rounded-2xl bg-red-700 px-10 py-6 text-xl font-bold text-white transition-all hover:bg-red-600 disabled:cursor-not-allowed disabled:bg-[#222222] disabled:text-white/40"
                        <?= empty($selectedGenreIds) ? "disabled" : "" ?>
                    >
                        Next: Choose Your Films
                    </button>

                </div>

            </form>


        <?php else: ?>

            <!-- STEP 2: MOVIES -->

            <div class="mb-12 text-center">

                <h1 class="text-3xl font-bold md:text-4xl">
                    Pick Your 3 Favourite Films
                </h1>

                <p class="mt-4 text-lg text-white/50">
                    Choose exactly three films that you love.
                </p>

                <p class="mt-6 text-xl font-bold">
                    Selected:
                    <span id="selected-count" class="text-red-500">
                        <?= count($selectedMovieIds) ?>
                    </span>
                    <span class="text-white/50">/ 3</span>
                </p>

            </div>

            <?php if (empty($movies)): ?>

                <p class="py-12 text-center text-white/60">
                    No movies are available for your selected genres.
                </p>

            <?php else: ?>

                <form action="mystery.php" method="post" id="movie-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= AdminPageController::escape($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="select_movies"
                    >

                    <div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4">

                        <?php foreach ($movies as $movie): ?>

                            <?php
                            $movieId = (int) $movie["movie_id"];

                            $isSelected = in_array(
                                $movieId,
                                $selectedMovieIds,
                                true
                            );

                            $releaseYear = !empty($movie["movie_release_date"])
                                ? date("Y", strtotime($movie["movie_release_date"]))
                                : "";
                            ?>

                            <label class="group cursor-pointer">

                                <input
                                    type="checkbox"
                                    name="movie_ids[]"
                                    value="<?= $movieId ?>"
                                    class="movie-checkbox peer sr-only"
                                    <?= $isSelected ? "checked" : "" ?>
                                >

                                <div class="overflow-hidden rounded-2xl border-[3px] border-[#292929] bg-[#1a1a1a] transition-all hover:border-red-700 peer-checked:border-red-600 peer-checked:bg-red-950/30">

                                    <div class="relative aspect-[2/3] overflow-hidden bg-[#252525]">

                                        <?php if (!empty($movie["movie_poster_url"])): ?>

                                            <img
                                                src="<?= AdminPageController::escape($movie["movie_poster_url"]) ?>"
                                                alt="<?= AdminPageController::escape($movie["movie_title"]) ?>"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div class="flex h-full items-center justify-center p-4 text-center text-white/30">
                                                No poster available
                                            </div>

                                        <?php endif; ?>

                                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>

                                        <div class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white/40 bg-black/60 text-white">
                                            <span class="selection-check hidden text-xl font-bold">✓</span>
                                        </div>

                                        <div class="absolute bottom-0 left-0 right-0 p-4">

                                            <h3 class="text-lg font-bold text-white">
                                                <?= AdminPageController::escape($movie["movie_title"]) ?>
                                            </h3>

                                            <p class="mt-1 text-sm text-white/60">
                                                <?= AdminPageController::escape($releaseYear) ?>

                                                <?php if (!empty($movie["movie_duration"])): ?>
                                                    · <?= (int) $movie["movie_duration"] ?> min
                                                <?php endif; ?>
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </label>

                        <?php endforeach; ?>

                    </div>

                    <p
                        id="movie-error"
                        class="mt-6 hidden text-center text-red-400"
                        role="alert"
                    >
                        Please select exactly 3 films.
                    </p>

                    <div class="mt-12 flex justify-center">

                        <button
                            type="submit"
                            id="movie-next-button"
                            class="min-w-[300px] rounded-2xl bg-red-700 px-10 py-6 text-xl font-bold text-white transition-all hover:bg-red-600 disabled:cursor-not-allowed disabled:bg-[#222222] disabled:text-white/40"
                            disabled
                        >
                            Next: Create Match
                        </button>

                    </div>

                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

<script>

    // STEP 1: Genre selection

    const genreForm = document.getElementById("genre-form");

    if (genreForm) {

        const nextButton = document.getElementById("next-button");

        const genreInputs = document.querySelectorAll(
            'input[name="genre_ids[]"]'
        );

        function updateGenreButton() {

            const selectedGenres = document.querySelectorAll(
                'input[name="genre_ids[]"]:checked'
            );

            nextButton.disabled = selectedGenres.length === 0;
        }

        genreInputs.forEach(input => {
            input.addEventListener("change", updateGenreButton);
        });

        genreForm.addEventListener("submit", event => {

            const selectedGenres = document.querySelectorAll(
                'input[name="genre_ids[]"]:checked'
            );

            if (selectedGenres.length === 0) {
                event.preventDefault();
                nextButton.disabled = true;
            }

        });

    }


    // STEP 2: Movie selection

    const movieForm = document.getElementById("movie-form");

    if (movieForm) {

        const movieInputs = document.querySelectorAll(".movie-checkbox");

        const selectedCount = document.getElementById("selected-count");

        const movieButton = document.getElementById("movie-next-button");

        const movieError = document.getElementById("movie-error");


        function updateMovieSelection() {

            const selected = document.querySelectorAll(
                ".movie-checkbox:checked"
            );

            selectedCount.textContent = selected.length;

            movieInputs.forEach(input => {

                input.disabled =
                    selected.length >= 3 && !input.checked;

                const check = input.parentElement.querySelector(
                    ".selection-check"
                );

                if (check) {
                    check.classList.toggle("hidden", !input.checked);
                }

            });

            movieButton.disabled = selected.length !== 3;

            movieError.classList.add("hidden");

        }


        movieInputs.forEach(input => {

            input.addEventListener("change", updateMovieSelection);

        });


        movieForm.addEventListener("submit", event => {

            const selected = document.querySelectorAll(
                ".movie-checkbox:checked"
            );

            if (selected.length !== 3) {

                event.preventDefault();

                movieError.classList.remove("hidden");

            }

        });


        updateMovieSelection();

    }

</script>

<?php include __DIR__ . "/footer.php"; ?>
