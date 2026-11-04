<?php

require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";

require_once __DIR__ . "/classes/AdminCsrfToken.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/MysteryGenreManager.php";
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
$csrfToken = $page->csrfToken;
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

            <div class="mt-7 h-[3px] flex-1 bg-white/15"></div>

            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 font-bold text-white/50">
                    2
                </div>
                <span class="mt-3 text-sm text-white/50">
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

        <!-- Heading -->
        <div class="mb-16 text-center">

            <h1 class="text-3xl font-bold md:text-4xl">
                What's your favourite genres?
            </h1>

            <p class="mt-4 text-lg text-white/50">
                Pick all the genres you enjoy.
            </p>

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

        <!-- Genre selection -->
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

            <!-- Next button -->
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

    </div>

</main>

<script>
    const genreForm = document.getElementById("genre-form");
    const nextButton = document.getElementById("next-button");

    const genreInputs = document.querySelectorAll(
        'input[name="genre_ids[]"]'
    );

    function updateNextButton() {
        const selectedGenres = document.querySelectorAll(
            'input[name="genre_ids[]"]:checked'
        );

        nextButton.disabled = selectedGenres.length === 0;
    }

    genreInputs.forEach((input) => {
        input.addEventListener("change", updateNextButton);
    });

    genreForm.addEventListener("submit", (event) => {
        const selectedGenres = document.querySelectorAll(
            'input[name="genre_ids[]"]:checked'
        );

        if (selectedGenres.length === 0) {
            event.preventDefault();
            nextButton.disabled = true;
        }
    });
</script>

<?php include __DIR__ . "/footer.php"; ?>
