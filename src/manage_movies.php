<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";

// escape texts before database push
function manage_movies_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

// security - rejecting pushs from other sessions
if (!isset($_SESSION["manage_movies_csrf"])) {
    $_SESSION["manage_movies_csrf"] = bin2hex(random_bytes(32));
}

// calling for genres names from IDs, deleting memory after
$genres = [];
$genreResult = $conn->query("SELECT genre_id, genre_name FROM GENRES ORDER BY genre_name");
if (!$genreResult) {
    die("Could not load genres: " . manage_movies_escape($conn->error));
}
while ($genre = $genreResult->fetch_assoc()) {
    $genres[(int) $genre["genre_id"]] = $genre["genre_name"];
}
$genreResult->free();

// message after saving creating or delete
$message = isset($_GET["saved"]) ? "Movie changes saved." : "";
if (isset($_GET["created"])) {
    $message = "Movie created.";
} elseif (isset($_GET["deleted"])) {
    $message = "Movie deleted.";
}
$error = "";
$isCreating = ($_GET["create"] ?? "") === "1";
$selectedMovieId = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
$movie = null;
$selectedGenreIds = [];

// post for changes, hash for security
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";
    if (!is_string($postedToken) || !hash_equals($_SESSION["manage_movies_csrf"], $postedToken)) {
        http_response_code(400);
        $error = "Your form session expired. Reload the page and try again.";
    } else {
        // what to do create update or delete
        $postAction = isset($_POST["action"]) && is_string($_POST["action"]) ? $_POST["action"] : "update";
        $isCreating = $postAction === "create";

        if ($postAction === "delete") {
            $deleteMovieIdInput = $_POST["movie_id"] ?? "";
            $movieIdToDelete = is_string($deleteMovieIdInput) || is_int($deleteMovieIdInput)
                ? filter_var($deleteMovieIdInput, FILTER_VALIDATE_INT)
                : false;
            if (!$movieIdToDelete || $movieIdToDelete < 1) {
                $error = "Select a valid movie to delete.";
            } else {
                try {
                    // FK removes genre links when their movie is deleted
                    $conn->begin_transaction();
                    $deleteMovie = $conn->prepare("DELETE FROM MOVIES WHERE movie_id = ?");
                    $deleteMovie->bind_param("i", $movieIdToDelete);
                    $deleteMovie->execute();
                    $deletedRows = $deleteMovie->affected_rows;
                    $deleteMovie->close();

                    if ($deletedRows !== 1) {
                        $conn->rollback();
                        $error = "That movie could not be found.";
                    } else {
                        $conn->commit();
                        redirect_to("manage_movies.php?deleted=1");
                    }
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    error_log("Movie deletion failed: " . $exception->getMessage());
                    $error = "Could not delete the movie. Check the server log for details.";
                }
            }
        } elseif ($postAction !== "create" && $postAction !== "update") {
            http_response_code(400);
            $error = "Invalid movie action.";
        } else {
        $updateMovieIdInput = $_POST["movie_id"] ?? "";
        $selectedMovieId = $isCreating
            ? null
            : ((is_string($updateMovieIdInput) || is_int($updateMovieIdInput))
                ? filter_var($updateMovieIdInput, FILTER_VALIDATE_INT)
                : false);
        // convert to strings to avoid errors
        $postedString = static function (string $key): string {
            return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : "";
        };
        $movie = [
            "movie_title" => $postedString("movie_title"),
            "movie_description" => $postedString("movie_description"),
            "movie_duration" => $postedString("movie_duration"),
            "movie_release_date" => $postedString("movie_release_date"),
            "movie_age_rating" => $postedString("movie_age_rating"),
            "movie_language" => $postedString("movie_language"),
            "movie_poster_url" => $postedString("movie_poster_url"),
            "movie_is_available" => isset($_POST["movie_is_available"]) ? 1 : 0,
        ];

        // takes only genere IDs that are valid ints and are in the database
        $postedGenreIds = $_POST["genre_ids"] ?? [];
        if (is_array($postedGenreIds)) {
            foreach ($postedGenreIds as $genreId) {
                if (!is_string($genreId) && !is_int($genreId)) {
                    continue;
                }
                $validGenreId = filter_var($genreId, FILTER_VALIDATE_INT);
                if ($validGenreId !== false && isset($genres[$validGenreId])) {
                    $selectedGenreIds[] = $validGenreId;
                }
            }
        }
        $selectedGenreIds = array_values(array_unique($selectedGenreIds));

        $duration = filter_var($movie["movie_duration"], FILTER_VALIDATE_INT);
        $date = DateTime::createFromFormat("!Y-m-d", $movie["movie_release_date"]);
        $dateIsValid = $date && $date->format("Y-m-d") === $movie["movie_release_date"];

        // valiadtes values and saves to DB
        if (!$isCreating && (!$selectedMovieId || $selectedMovieId < 1)) {
            $error = "Select a valid movie.";
        } elseif ($movie["movie_title"] === "" || strlen($movie["movie_title"]) > 255) {
            $error = "The title is required and must be no more than 255 characters.";
        } elseif ($duration === false || $duration < 1) {
            $error = "Duration must be a positive number of minutes.";
        } elseif (!$dateIsValid) {
            $error = "Enter a valid release date.";
        } elseif ($movie["movie_age_rating"] === "" || strlen($movie["movie_age_rating"]) > 10) {
            $error = "Age rating is required and must be no more than 10 characters.";
        } elseif ($movie["movie_language"] === "" || strlen($movie["movie_language"]) > 50) {
            $error = "Language is required and must be no more than 50 characters.";
        } elseif (strlen($movie["movie_poster_url"]) > 255) {
            $error = "Poster URL must be no more than 255 characters.";
        } elseif ($movie["movie_poster_url"] !== "" && filter_var($movie["movie_poster_url"], FILTER_VALIDATE_URL) === false) {
            $error = "Enter a valid poster URL.";
        } else {
                // saves movies and genre at the same time
            try {
                $conn->begin_transaction();

                if (!$isCreating) {
                    // is the movi ID valid check
                    $checkMovie = $conn->prepare("SELECT movie_id FROM MOVIES WHERE movie_id = ?");
                    $checkMovie->bind_param("i", $selectedMovieId);
                    $checkMovie->execute();
                    $checkMovie->store_result();
                    $movieExists = $checkMovie->num_rows === 1;
                    $checkMovie->close();

                    if (!$movieExists) {
                        throw new DomainException("That movie could not be found.");
                    }
                }

                if ($isCreating) {
                    // creating ID with mySQL
                    $insertMovie = $conn->prepare(
                        "INSERT INTO MOVIES
                         (movie_title, movie_description, movie_duration, movie_release_date,
                          movie_age_rating, movie_language, movie_poster_url, movie_is_available)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $description = $movie["movie_description"] === "" ? null : $movie["movie_description"];
                    $posterUrl = $movie["movie_poster_url"] === "" ? null : $movie["movie_poster_url"];
                    $insertMovie->bind_param(
                        // bind_param types: s = string, i = integer
                        "ssissssi",
                        $movie["movie_title"],
                        $description,
                        $duration,
                        $movie["movie_release_date"],
                        $movie["movie_age_rating"],
                        $movie["movie_language"],
                        $posterUrl,
                        $movie["movie_is_available"]
                    );
                    $insertMovie->execute();
                    $selectedMovieId = $conn->insert_id;
                    $insertMovie->close();
                } else {
                    // placeholders keep submitted values separate from the SQL command
                    $updateMovie = $conn->prepare(
                        "UPDATE MOVIES
                         SET movie_title = ?, movie_description = ?, movie_duration = ?,
                             movie_release_date = ?, movie_age_rating = ?, movie_language = ?,
                             movie_poster_url = ?, movie_is_available = ?
                         WHERE movie_id = ?"
                    );
                    $description = $movie["movie_description"] === "" ? null : $movie["movie_description"];
                    $posterUrl = $movie["movie_poster_url"] === "" ? null : $movie["movie_poster_url"];
                    $updateMovie->bind_param(
                        // the final integer is the movie ID used by WHERE
                        "ssissssii",
                        $movie["movie_title"],
                        $description,
                        $duration,
                        $movie["movie_release_date"],
                        $movie["movie_age_rating"],
                        $movie["movie_language"],
                        $posterUrl,
                        $movie["movie_is_available"],
                        $selectedMovieId
                    );
                    $updateMovie->execute();
                    $updateMovie->close();

                    // replace all old links for genre with new ones
                    $deleteGenres = $conn->prepare("DELETE FROM MOVIE_GENRES WHERE movie_id = ?");
                    $deleteGenres->bind_param("i", $selectedMovieId);
                    $deleteGenres->execute();
                    $deleteGenres->close();
                }

                // join table
                if ($selectedGenreIds !== []) {
                    $insertGenre = $conn->prepare(
                        "INSERT INTO MOVIE_GENRES (movie_id, genre_id) VALUES (?, ?)"
                    );
                    foreach ($selectedGenreIds as $genreId) {
                        $insertGenre->bind_param("ii", $selectedMovieId, $genreId);
                        $insertGenre->execute();
                    }
                    $insertGenre->close();
                }

                $conn->commit();
                // redirect after POST so refreshing the success page will not submit the form again
                $successParameter = $isCreating ? "created=1" : "saved=1";
                redirect_to("manage_movies.php?id=" . $selectedMovieId . "&" . $successParameter);
            } catch (DomainException $exception) {
                $conn->rollback();
                $error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                error_log("Movie save failed: " . $exception->getMessage());
                $error = "Could not save the movie. Check the server log for details.";
            }
        }
        }
    }
}

// load titles for search
$movieResult = $conn->query(
    "SELECT movie_id, movie_title, movie_description, movie_duration, movie_release_date,
            movie_age_rating, movie_language, movie_poster_url, movie_is_available
     FROM MOVIES ORDER BY movie_title"
);
if (!$movieResult) {
    die("Could not load movies: " . manage_movies_escape($conn->error));
}
$movies = [];
while ($movieRow = $movieResult->fetch_assoc()) {
    $movies[] = $movieRow;
}
$movieResult->free();

// load that movie and its current genre links into the form when ?id=
if ($movie === null && $selectedMovieId) {
    $loadMovie = $conn->prepare(
        "SELECT movie_id, movie_title, movie_description, movie_duration, movie_release_date,
                movie_age_rating, movie_language, movie_poster_url, movie_is_available
         FROM MOVIES WHERE movie_id = ?"
    );
    $loadMovie->bind_param("i", $selectedMovieId);
    $loadMovie->execute();
    $result = $loadMovie->get_result();
    $movie = $result->fetch_assoc() ?: null;
    $loadMovie->close();

    if ($movie) {
        $loadGenres = $conn->prepare("SELECT genre_id FROM MOVIE_GENRES WHERE movie_id = ?");
        $loadGenres->bind_param("i", $selectedMovieId);
        $loadGenres->execute();
        $genreRows = $loadGenres->get_result();
        while ($genreRow = $genreRows->fetch_assoc()) {
            $selectedGenreIds[] = (int) $genreRow["genre_id"];
        }
        $loadGenres->close();
    } elseif ($error === "") {
        $error = "That movie could not be found.";
    }
}

// blank set of values makes the same form reusable for creating a new movie
if ($isCreating && $movie === null) {
    $movie = [
        "movie_title" => "",
        "movie_description" => "",
        "movie_duration" => "",
        "movie_release_date" => "",
        "movie_age_rating" => "",
        "movie_language" => "",
        "movie_poster_url" => "",
        "movie_is_available" => 1,
    ];
}
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto min-h-[650px] max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold">Manage movies</h1>
    <p class="mt-2 text-sm text-white/60">Edit movie details and assign one or more genres.</p>

    <?php if ($message !== ""): ?>
        <p class="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-100" role="status"><?= manage_movies_escape($message) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-100" role="alert"><?= manage_movies_escape($error) ?></p>
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
                value="<?= $movie !== null ? manage_movies_escape($movie["movie_title"]) : "" ?>"
            >
        </label>
        <div id="movie-recommendations" class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-white/15 bg-[#1a1a1a] shadow-xl" role="listbox"></div>
        <p class="mt-2 text-xs text-white/50" id="movie-search-status" aria-live="polite">Type at least one letter to see matching movies.</p>
        <!-- Hidden elements expose movie IDs and titles to JavaScript without another request. -->
        <div id="movie-search-data" class="hidden" aria-hidden="true">
            <?php foreach ($movies as $movieOption): ?>
                <span data-id="<?= (int) $movieOption["movie_id"] ?>" data-title="<?= manage_movies_escape($movieOption["movie_title"]) ?>"></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($movie !== null): ?>
        <form action="manage_movies.php<?= $isCreating ? "?create=1" : "?id=" . (int) $selectedMovieId ?>" method="post" class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <!-- Hidden values tell the server which operation/movie this form represents. -->
            <input type="hidden" name="csrf_token" value="<?= manage_movies_escape($_SESSION["manage_movies_csrf"]) ?>">
            <input type="hidden" name="action" value="<?= $isCreating ? "create" : "update" ?>">
            <?php if (!$isCreating): ?>
                <input type="hidden" name="movie_id" value="<?= (int) $selectedMovieId ?>">
            <?php endif; ?>

            <label class="block text-sm font-medium" for="movie-title">
                Title
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-title" name="movie_title" maxlength="255" value="<?= manage_movies_escape($movie["movie_title"]) ?>" required>
            </label>

            <label class="block text-sm font-medium" for="movie-description">
                Description
                <textarea class="mt-2 min-h-28 w-full rounded-lg border border-white/15 bg-[#1a1a1a] p-3 text-background" id="movie-description" name="movie_description"><?= manage_movies_escape($movie["movie_description"] ?? "") ?></textarea>
            </label>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <label class="block text-sm font-medium" for="movie-duration">
                    Duration (minutes)
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-duration" name="movie_duration" type="number" min="1" value="<?= manage_movies_escape((string) $movie["movie_duration"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-age-rating">
                    Age rating
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-age-rating" name="movie_age_rating" maxlength="10" value="<?= manage_movies_escape($movie["movie_age_rating"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-language">
                    Language
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-language" name="movie_language" maxlength="50" value="<?= manage_movies_escape($movie["movie_language"]) ?>" required>
                </label>
                <label class="block text-sm font-medium" for="movie-release-date">
                    Release date
                    <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-release-date" name="movie_release_date" type="date" value="<?= manage_movies_escape($movie["movie_release_date"]) ?>" required>
                </label>
            </div>

            <label class="block text-sm font-medium" for="movie-poster-url">
                Poster URL (optional)
                <input class="mt-2 h-12 w-full rounded-lg border border-white/15 bg-[#1a1a1a] px-3 text-background" id="movie-poster-url" name="movie_poster_url" type="url" maxlength="255" value="<?= manage_movies_escape($movie["movie_poster_url"] ?? "") ?>">
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
                                <?= manage_movies_escape($genreName) ?>
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
                        This will permanently delete “<?= manage_movies_escape($movie["movie_title"]) ?>” and its genre assignments. This action cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button class="rounded-lg border border-white/15 px-4 py-2.5 text-sm font-semibold hover:bg-white/5" id="cancel-delete-movie" type="button">Cancel</button>
                        <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600" type="submit" form="delete-movie-form">Delete movie</button>
                    </div>
                </div>
            </dialog>
            <form action="manage_movies.php?id=<?= (int) $selectedMovieId ?>" id="delete-movie-form" method="post" class="hidden">
                <input type="hidden" name="csrf_token" value="<?= manage_movies_escape($_SESSION["manage_movies_csrf"]) ?>">
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