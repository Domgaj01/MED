    <footer class="border-t border-white/10 bg-[#191919]">
        <div class="mx-auto grid max-w-[1440px] grid-cols-4 gap-12 px-[clamp(24px,4vw,56px)] py-14 max-[900px]:grid-cols-2 max-[900px]:gap-x-8 max-[900px]:gap-y-10 max-[480px]:grid-cols-1 max-[480px]:gap-8 max-[480px]:py-10">
            <div>
                <a class="inline-flex focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background" href="./index.php" aria-label="Mystery Cinema - home">
                    <img class="block h-auto w-[165px]" src="./pictures/mclogo.png" alt="Mystery Cinema">
                </a>
                <p class="mt-5 max-w-[300px] text-sm leading-6 text-[#a6a6a6]">Good movies find the right people. A surprise worth sharing.</p>
            </div>

            <nav aria-label="Explore">
                <h2 class="mb-5 text-sm font-bold uppercase tracking-[0.08em] text-background">Explore</h2>
                <ul class="space-y-3 text-sm text-[#a6a6a6]">
                    <li><a class="transition-colors hover:text-background" href="./index.php">Home</a></li>
                    <li><a class="transition-colors hover:text-background" href="./movies.php">Movies</a></li>
                    <li><a class="transition-colors hover:text-background" href="./mystery-cinema.php">Mystery Cinema</a></li>
                    <li><a class="transition-colors hover:text-background" href="./food-and-drinks.php">Food &amp; Drinks</a></li>
                    <li><a class="transition-colors hover:text-background" href="./about.php">About</a></li>
                </ul>
            </nav>

            <nav aria-label="Account">
                <h2 class="mb-5 text-sm font-bold uppercase tracking-[0.08em] text-background">Account</h2>
                <ul class="space-y-3 text-sm text-[#a6a6a6]">
                    <?php if (logged_in()): ?>
                        <li><a class="transition-colors hover:text-background" href="./logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a class="transition-colors hover:text-background" href="./login.php">Login</a></li>
                    <?php endif; ?>
                    <li><a class="transition-colors hover:text-background" href="./profile.php">My Profile</a></li>
                    <li><a class="transition-colors hover:text-background" href="mailto:hej@mysterycinema.dk">Contact Us</a></li>
                </ul>
            </nav>

            <div>
                <h2 class="mb-5 text-sm font-bold uppercase tracking-[0.08em] text-background">Visit us</h2>
                <address class="space-y-3 text-sm not-italic leading-6 text-[#a6a6a6]">
                    <p>Filmstræde 12<br>1234 Copenhagen</p>
                    <p><a class="transition-colors hover:text-background" href="tel:+4570123456">+45 70 12 34 56</a></p>
                    <p><a class="transition-colors hover:text-background" href="mailto:hej@mysterycinema.dk">hej@mysterycinema.dk</a></p>
                </address>

                <h3 class="mb-2 mt-6 text-sm font-bold uppercase tracking-[0.08em] text-background">Opening hours</h3>
                <p class="text-sm leading-6 text-[#a6a6a6]">Mon–Thu: 13:00–23:00<br>Fri–Sun: 12:00–01:00</p>
            </div>
        </div>

        <div class="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-4 border-t border-white/10 px-[clamp(24px,4vw,56px)] py-5 text-sm text-[#929292] max-[600px]:flex-col max-[600px]:items-start">
            <p>© <?= date('Y') ?> Mystery Cinema. All rights reserved.</p>
            <nav class="flex flex-wrap gap-x-6 gap-y-2" aria-label="Legal">
                <a class="transition-colors hover:text-background" href="./privacy-policy.php">Privacy Policy</a>
                <a class="transition-colors hover:text-background" href="./terms.php">Terms &amp; Conditions</a>
                <a class="transition-colors hover:text-background" href="./cookie-settings.php">Cookie Settings</a>
            </nav>
        </div>
    </footer>

    <script>
        const movieCards = [...document.querySelectorAll(".movie-card")];
        const movieSearch = document.querySelector("#movie-search");
        const movieCount = document.querySelector("#movie-count");
        const movieResults = document.querySelector("#movie-results");
        const noMoviesMessage = document.querySelector("#no-movies");
        const genreFilters = [...document.querySelectorAll(".genre-filter")];
        let activeGenre = "all";

        function filterMovies() {
            const searchTerm = movieSearch.value.trim().toLowerCase();
            let visibleCount = 0;

            for (const card of movieCards) {
                const matchesGenre = activeGenre === "all" || card.dataset.genres.toLowerCase().includes(`|${activeGenre.toLowerCase()}|`);
                const matchesSearch = card.dataset.title.includes(searchTerm);
                const isVisible = matchesGenre && matchesSearch;

                card.hidden = !isVisible;
                visibleCount += Number(isVisible);
            }

            movieCount.textContent = visibleCount;
            movieResults.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? "film" : "films"}`;
            noMoviesMessage.classList.toggle("hidden", visibleCount !== 0);
        }

        if (movieSearch) {
            for (const filter of genreFilters) {
                filter.addEventListener("click", () => {
                    activeGenre = filter.dataset.genre;

                    for (const button of genreFilters) {
                        const isActive = button === filter;
                        button.classList.toggle("is-active", isActive);
                        button.classList.toggle("border-accent", isActive);
                        button.classList.toggle("bg-accent", isActive);
                        button.classList.toggle("text-white", isActive);
                        button.classList.toggle("border-white/[0.07]", !isActive);
                        button.classList.toggle("bg-[#1a1a1a]", !isActive);
                        button.classList.toggle("text-[#a6a6a6]", !isActive);
                        button.setAttribute("aria-pressed", String(isActive));
                    }

                    filterMovies();
                });
            }

            movieSearch.addEventListener("input", filterMovies);
        }
    </script>
</body>
</html>