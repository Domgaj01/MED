<?php include __DIR__ . '/header.php'; ?>

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
                    Choose a film the normal way — or hand over control. Tell us what you love, and we’ll find the perfect movie for you. You won’t know what it is until the lights go down.
                </p>

                <div class="mt-11 flex flex-wrap items-center justify-center gap-4 max-[480px]:mt-8 max-[480px]:w-full max-[480px]:flex-col">
                    <a class="inline-flex min-h-16 items-center justify-center rounded-[14px] bg-accent px-9 text-base font-bold text-white shadow-[0_10px_35px_rgba(185,5,4,0.18)] transition-colors hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:min-h-14 max-[480px]:w-full max-[480px]:max-w-[300px]" href="#mystery-cinema">
                        Try Mystery Cinema
                    </a>
                    <a class="inline-flex min-h-16 items-center justify-center rounded-[14px] border border-white/15 bg-white/10 px-9 text-base font-bold text-background transition-colors hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-background max-[480px]:min-h-14 max-[480px]:w-full max-[480px]:max-w-[300px]" href="#movies">
                        See What’s Showing
                    </a>
                </div>
            </div>
        </section>

    </main>
<?php include __DIR__ . '/footer.php'; ?>