<?php
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/SiteContentManager.php";

$contentManager = new SiteContentManager($conn);
$siteSettings = $contentManager->getSettings();
$todayScreenings = $contentManager->getTodayScreenings();
$publishedNews = $contentManager->getPublishedNews();
$todayLabel = (new DateTimeImmutable("today"))->format("l, j F");
include __DIR__ . '/header.php';
?>

    <main>
        <section class="hero relative isolate flex min-h-[calc(100svh-68px)] items-center justify-center overflow-hidden px-5 py-14 text-center max-[480px]:px-4 max-[480px]:py-12" id="home">
            <div class="relative z-10 mx-auto flex w-full max-w-[850px] flex-col items-center">
                <p class="mb-7 rounded-full border border-accent/30 bg-accent/10 px-[18px] py-2 text-xs font-bold uppercase tracking-[0.12em] text-accent max-[480px]:mb-6 max-[480px]:text-[10px]">
                    Esbjerg’s boldest cinema experience
                </p>

                <h1 class="text-[clamp(2.8rem,6vw,5rem)] font-black uppercase leading-[1.01] tracking-[-0.055em] text-background">
                    <span class="block">More than</span>
                    <span class="block">a movie.</span>
                    <span class="block text-accent">It’s a mystery.</span>
                </h1>

                <p class="mt-7 max-w-[760px] text-[clamp(1rem,1.55vw,1.375rem)] leading-[1.65] text-[#dedada] max-[480px]:mt-5">
                    <?= nl2br(AdminPageController::escape($siteSettings["company_description"])) ?>
                </p>

                <div class="mt-11 flex flex-wrap items-center justify-center gap-4 max-[480px]:mt-8 max-[480px]:w-full max-[480px]:flex-col">
                    <a class="inline-flex min-h-16 items-center justify-center rounded-[14px] bg-accent px-9 text-base font-bold text-white shadow-[0_10px_35px_rgba(185,5,4,0.18)] transition-colors hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:min-h-14 max-[480px]:w-full max-[480px]:max-w-[300px]" href="#mystery-cinema">
                        Try Mystery Cinema
                    </a>
                    <a class="inline-flex min-h-16 items-center justify-center rounded-[14px] border border-white/15 bg-white/10 px-9 text-base font-bold text-background transition-colors hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:min-h-14 max-[480px]:w-full max-[480px]:max-w-[300px]" href="#today">
                        See What’s Showing
                    </a>
                </div>
            </div>
        </section>

        <section class="bg-[#141414] px-6 py-20" id="today" aria-labelledby="today-title">
            <div class="mx-auto max-w-[1200px]">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-accent"><?= AdminPageController::escape($todayLabel) ?></p>
                <h2 class="text-3xl font-black uppercase tracking-[-0.04em]" id="today-title">Today’s showings</h2>
                <p class="mt-2 text-sm text-white/60">Upcoming screenings at Mystery Cinema.</p>

                <?php if ($todayScreenings === []): ?>
                    <p class="mt-6 rounded-xl border border-white/10 p-5 text-sm text-white/60">There are no more screenings scheduled for today. Check back soon for upcoming showtimes.</p>
                <?php else: ?>
                    <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($todayScreenings as $screening): ?>
                            <li class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                                <p class="text-sm font-bold text-accent"><?= AdminPageController::escape(date("H:i", strtotime($screening["start_time"]))) ?></p>
                                <h3 class="mt-2 text-lg font-bold"><?= AdminPageController::escape($screening["movie_title"]) ?></h3>
                                <p class="mt-1 text-sm text-white/60">
                                    <?= AdminPageController::escape($screening["hall_name"]) ?>
                                    <?php if ($screening["movie_age_rating"] !== ""): ?>
                                        · <?= AdminPageController::escape($screening["movie_age_rating"]) ?>
                                    <?php endif; ?>
                                </p>
                                <a class="mt-4 inline-flex text-sm font-semibold text-accent transition-colors hover:text-red-300" href="book-tickets.php?movie_id=<?= (int) $screening["movie_id"] ?>&amp;date=<?= AdminPageController::escape(date("Y-m-d", strtotime($screening["start_time"]))) ?>&amp;screening_id=<?= (int) $screening["screening_id"] ?>">
                                    Book this screening <span class="ml-1" aria-hidden="true">→</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="bg-night px-6 py-20" id="news" aria-labelledby="news-title">
            <div class="mx-auto max-w-[1200px]">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-accent">From Mystery Cinema</p>
                <h2 class="text-3xl font-black uppercase tracking-[-0.04em]" id="news-title">Latest news</h2>
                <?php if ($publishedNews === []): ?>
                    <p class="mt-6 text-sm text-white/60">There is no news to share right now. Check back soon.</p>
                <?php else: ?>
                    <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($publishedNews as $article): ?>
                            <article class="rounded-xl border border-white/10 bg-white/[0.03] p-6">
                                <p class="text-xs text-white/50"><?= AdminPageController::escape(date("d M Y", strtotime($article["created_at"]))) ?></p>
                                <h3 class="mt-2 text-lg font-bold"><?= AdminPageController::escape($article["title"]) ?></h3>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-white/70"><?= AdminPageController::escape($article["body"]) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php include __DIR__ . '/footer.php'; ?>