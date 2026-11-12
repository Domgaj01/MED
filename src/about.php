<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/AdminPageController.php";
require_once __DIR__ . "/classes/SiteContentManager.php";
$siteSettings = (new SiteContentManager($conn))->getSettings();
include __DIR__ . '/header.php';
?>

<main>

    <!-- HERO SECTION -->
    <section class="relative isolate flex min-h-[560px] items-center justify-center overflow-hidden bg-night px-5 py-10 text-center max-[480px]:min-h-[480px] max-[480px]:px-4">

        <div class="relative z-10 mx-auto flex w-full max-w-[850px] flex-col items-center">

            <p class="mb-7 rounded-full border border-accent/30 bg-accent/10 px-[18px] py-2 text-xs font-bold uppercase tracking-[0.12em] text-accent max-[480px]:mb-6 max-[480px]:text-[10px]">
                Our story
            </p>

            <h1 class="text-[clamp(2.4rem,5vw,4.25rem)] font-black uppercase leading-[1.05] tracking-[-0.055em] text-background">
                <span class="block">Good movies</span>
                <span class="block">find the</span>
                <span class="block text-accent">right people.</span>
            </h1>

            <p class="mt-7 max-w-[760px] text-[clamp(1rem,1.55vw,1.375rem)] leading-[1.65] text-[#dedada] max-[480px]:mt-5">
                Discover a cinema experience shaped around great films and the joy of surprise.
            </p>

        </div>
    </section>


    <!-- OUR STORY SECTION -->
    <section class="mx-auto grid max-w-[1200px] items-center gap-14 bg-night px-6 py-24 md:grid-cols-2 md:gap-20">

        <div>

            <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-accent">
                How it started
            </p>

            <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-black uppercase leading-[1.05] tracking-[-0.04em] text-background">
                A different kind of cinema experience.
            </h2>

            <p class="mt-7 text-base leading-[1.8] text-[#dedada]">
                <?= nl2br(AdminPageController::escape($siteSettings["company_description"])) ?>
            </p>

        </div>

        <div class="relative flex min-h-[350px] items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-[#141414]">

            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(185,5,4,0.12),transparent_70%)]"></div>

            <div class="relative mx-8 border-l-2 border-accent py-2 pl-7">

                <p class="text-[clamp(1.4rem,2.5vw,2rem)] font-bold leading-relaxed text-[#dedada]">
                    "The best film you'll ever see is one you didn't know you needed."
                </p>

            </div>
        </div>

    </section>


    <!-- OUR VALUES SECTION -->
    <section class="bg-[#141414] px-6 py-24">

        <div class="mx-auto max-w-[1200px]">

            <div class="mx-auto mb-16 max-w-[650px] text-center">

                <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-accent">
                    What makes us different
                </p>

                <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-black uppercase leading-[1.05] tracking-[-0.04em] text-background">
                    The experience matters.
                </h2>

            </div>

            <div class="grid gap-6 md:grid-cols-3">

                <article class="rounded-2xl border border-white/10 bg-[#1c1c1c] p-8 transition-colors duration-300 hover:border-accent/60">

                    <p class="mb-6 text-4xl font-black text-accent">01</p>

                    <h3 class="text-xl font-bold uppercase tracking-tight text-background">
                        The mystery
                    </h3>

                    <p class="mt-4 text-base leading-[1.8] text-[#dedada]">
                        Step outside your comfort zone and let us surprise you
                        with a film you might never have chosen yourself.
                    </p>

                </article>

                <article class="rounded-2xl border border-white/10 bg-[#1c1c1c] p-8 transition-colors duration-300 hover:border-accent/60">

                    <p class="mb-6 text-4xl font-black text-accent">02</p>

                    <h3 class="text-xl font-bold uppercase tracking-tight text-background">
                        The community
                    </h3>

                    <p class="mt-4 text-base leading-[1.8] text-[#dedada]">
                        Cinema brings people together. Every screening is
                        an opportunity to share a moment with others.
                    </p>

                </article>

                <article class="rounded-2xl border border-white/10 bg-[#1c1c1c] p-8 transition-colors duration-300 hover:border-accent/60">

                    <p class="mb-6 text-4xl font-black text-accent">03</p>

                    <h3 class="text-xl font-bold uppercase tracking-tight text-background">
                        The discovery
                    </h3>

                    <p class="mt-4 text-base leading-[1.8] text-[#dedada]">
                        Discover new stories, unexpected genres and films
                        that stay with you long after the credits roll.
                    </p>

                </article>

            </div>
        </div>

    </section>


    <!-- CONTACT FORM SECTION -->
    <section id="contact" class="bg-[#141414] px-6 py-16">

        <div class="mx-auto max-w-[620px]">

            <div class="mb-9 text-center">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-accent">
                    Get in touch
                </p>

                <h2 class="text-3xl font-black uppercase text-background max-[480px]:text-2xl">
                    We'd love to hear from you.
                </h2>

                <p class="mt-4 text-sm leading-relaxed text-[#dedada]">
                    Have a question about Mystery Cinema? Send us a message!
                </p>
            </div>

            <form action="contact.php" method="POST" class="space-y-4">

                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-background">
                        Your name
                    </label>
                    <input type="text" id="name" name="name" required
                        placeholder="Enter your name"
                        class="w-full rounded-xl border border-white/10 bg-[#1c1c1c] px-4 py-3 text-sm text-white outline-none focus:border-accent">
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-background">
                        Email address
                    </label>
                    <input type="email" id="email" name="email" required
                        placeholder="Enter your email"
                        class="w-full rounded-xl border border-white/10 bg-[#1c1c1c] px-4 py-3 text-sm text-white outline-none focus:border-accent">
                </div>

                <div>
                    <label for="subject" class="mb-2 block text-sm font-semibold text-background">
                        Subject
                    </label>
                    <input type="text" id="subject" name="subject" required
                        placeholder="What is your message about?"
                        class="w-full rounded-xl border border-white/10 bg-[#1c1c1c] px-4 py-3 text-sm text-white outline-none focus:border-accent">
                </div>

                <div>
                    <label for="message" class="mb-2 block text-sm font-semibold text-background">
                        Your message
                    </label>
                    <textarea id="message" name="message" rows="4" required
                        placeholder="Write your message here..."
                        class="w-full rounded-xl border border-white/10 bg-[#1c1c1c] px-4 py-3 text-sm text-white outline-none focus:border-accent"></textarea>
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-accent px-8 py-3.5 text-sm font-bold text-white transition-colors hover:bg-accent-dark">
                    Send message
                </button>

            </form>

        </div>

    </section>


    <!-- CALL TO ACTION -->
    <section class="bg-night px-6 py-28 text-center">

        <div class="mx-auto flex max-w-[900px] flex-col items-center">

            <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-accent">
                Your next favourite film awaits
            </p>

            <h2 class="text-[clamp(2.4rem,5vw,4.25rem)] font-black uppercase leading-[1.05] tracking-[-0.055em] text-background">
                Ready to experience the mystery?
            </h2>

            <p class="mt-7 max-w-[760px] text-[clamp(1rem,1.55vw,1.375rem)] leading-[1.65] text-[#dedada]">
                Leave the choice to us and discover the magic of Mystery Cinema.
            </p>

            <a href="./movies.php"
               class="mt-11 inline-flex min-h-16 items-center justify-center rounded-[14px] bg-accent px-9 text-base font-bold text-white shadow-[0_10px_35px_rgba(185,5,4,0.18)] transition-colors hover:bg-accent-dark">
                Explore our movies
            </a>

        </div>

    </section>


</main>

<?php include __DIR__ . '/footer.php'; ?>
