<?php require_once __DIR__ . "/parts/db.php"; ?>
<?php

$sql = "SELECT
            m.movie_id,
            m.movie_title,
            m.movie_duration,
            m.movie_release_date,
            m.movie_age_rating,
            GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR '|') AS genres
        FROM MOVIES AS m
        LEFT JOIN MOVIE_GENRES AS mg ON m.movie_id = mg.movie_id
        LEFT JOIN GENRES AS g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id, m.movie_title, m.movie_duration, m.movie_release_date, m.movie_age_rating
        ORDER BY m.movie_title";

$result = $conn->query($sql);

if (!$result) {
    die("Movie query failed: " . htmlspecialchars($conn->error));
}

$movies = [];
$genres = [];

while ($row = $result->fetch_assoc()) {
    $row["genres"] = $row["genres"] ? explode("|", $row["genres"]) : [];
    $movies[] = $row;

    foreach ($row["genres"] as $genre) {
        $genres[$genre] = $genre;
    }
}

ksort($genres);

$result->free();
$conn->close();
?>
<?php include __DIR__ . '/header.php'; ?>

    <main>
        
        <section class="movies-section border-t border-white/10" id="movies" aria-labelledby="movies-title">
            <div class="movies-intro border-b border-white/10 bg-[#181818] px-[clamp(24px,4vw,56px)] py-12 max-[480px]:py-9">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-accent">What’s on</p>
                <h2 class="text-4xl font-black uppercase tracking-[-0.04em] max-[480px]:text-3xl" id="movies-title">Now showing</h2>
                <p class="mt-2 text-sm text-[#a6a6a6]"><span id="movie-count"><?= count($movies) ?></span> films on screen — or let us choose for you.</p>
            </div>

            <div class="mx-auto max-w-[1440px] px-[clamp(24px,4vw,56px)] py-10 max-[480px]:py-7">
                <div class="movie-controls mb-10 flex items-center gap-4 max-[900px]:flex-col max-[900px]:items-stretch">
                    <label class="relative block min-w-[250px] flex-1 max-[900px]:w-full" for="movie-search">
                        <span class="sr-only">Search movies</span>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-[#929292]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"></circle>
                            <path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                        </svg>
                        <input class="h-[46px] w-full rounded-xl border border-white/[0.07] bg-[#1a1a1a] pl-11 pr-4 text-sm text-background outline-none placeholder:text-[#777] focus:border-accent" id="movie-search" type="search" placeholder="Search movies..." autocomplete="off">
                    </label>

                    <div class="flex flex-wrap gap-2.5" role="group" aria-label="Filter movies by genre">
                        <button class="genre-filter is-active rounded-lg border border-accent bg-accent px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-dark" type="button" data-genre="all" aria-pressed="true">All</button>
                        <?php foreach ($genres as $genre): ?>
                            <button class="genre-filter rounded-lg border border-white/[0.07] bg-[#1a1a1a] px-4 py-3 text-sm font-medium text-[#a6a6a6] transition-colors hover:border-white/20 hover:text-background" type="button" data-genre="<?= htmlspecialchars($genre) ?>" aria-pressed="false"><?= htmlspecialchars($genre) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a class="mb-10 flex items-center gap-4 rounded-xl border border-accent/20 bg-[#191919] p-5 transition-colors hover:border-accent/50 max-[600px]:items-start max-[600px]:p-4" href="#mystery-cinema">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-accent/25 bg-accent/10 text-lg font-bold text-accent" aria-hidden="true">?</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-base font-bold text-background">Can’t decide? Try Mystery Cinema</span>
                        <span class="mt-0.5 block text-sm text-[#a6a6a6]">Tell us what you love — we’ll pick the perfect film for you.</span>
                    </span>
                    <span class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg bg-accent px-5 text-sm font-bold text-white transition-colors hover:bg-accent-dark max-[600px]:hidden">Try Mystery</span>
                </a>

                <p class="mb-5 text-sm text-[#929292]" id="movie-results" aria-live="polite">Showing <?= count($movies) ?> <?= count($movies) === 1 ? 'film' : 'films' ?></p>

                <div class="grid grid-cols-4 gap-6 max-[1050px]:grid-cols-3 max-[760px]:grid-cols-2 max-[480px]:grid-cols-1 max-[480px]:gap-4" id="movie-grid">
                    <?php foreach ($movies as $movie): ?>
                        <?php
                        $movieGenres = $movie['genres'];
                        $hours = intdiv((int) $movie['movie_duration'], 60);
                        $minutes = (int) $movie['movie_duration'] % 60;
                        ?>
                        <article class="movie-card overflow-hidden rounded-xl border border-white/[0.08] bg-[#151515] transition-transform duration-200 hover:-translate-y-1 hover:border-white/20" data-title="<?= htmlspecialchars(strtolower($movie['movie_title'])) ?>" data-genres="<?= htmlspecialchars('|' . implode('|', $movieGenres) . '|') ?>">
                            <div class="movie-poster relative flex aspect-[2/2.55] items-center justify-center overflow-hidden">
                                <?php if ($movieGenres !== []): ?>
                                    <span class="absolute left-3 top-3 rounded bg-accent px-2.5 py-1 text-xs font-bold"><?= htmlspecialchars($movieGenres[0]) ?></span>
                                <?php endif; ?>
                                <?php if ($movie['movie_age_rating'] !== ''): ?>
                                    <span class="absolute right-3 top-3 rounded bg-black/75 px-2 py-1 text-xs font-bold"><?= htmlspecialchars($movie['movie_age_rating']) ?></span>
                                <?php endif; ?>
                                <img class="w-2/3 max-w-[190px] object-contain opacity-75" src="./pictures/mclogo.png" alt="" loading="lazy">
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-base font-bold"><?= htmlspecialchars($movie['movie_title']) ?></h3>
                                    <span class="shrink-0 text-xs text-[#929292]"><?= date('Y', strtotime($movie['movie_release_date'])) ?></span>
                                </div>
                                <p class="mt-1 text-sm text-[#929292]"><?= htmlspecialchars(implode(' · ', $movieGenres)) ?> · <?= $hours ?>h <?= sprintf('%02d', $minutes) ?>m</p>
                                <a class="mt-4 inline-flex text-sm font-semibold text-accent transition-colors hover:text-red-300" href="#book-tickets">View showtimes <span class="ml-1" aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="hidden py-16 text-center text-[#a6a6a6]" id="no-movies">No movies found. Try a different search or genre.</p>
                <?php if (count($movies) === 0): ?>
                    <p class="py-16 text-center text-[#a6a6a6]">No movies found in the database yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php include __DIR__ . '/footer.php'; ?>