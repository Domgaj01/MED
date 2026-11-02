<?php
require_once __DIR__ . "/parts/session.php";
require_once __DIR__ . "/parts/db.php";
require_once __DIR__ . "/classes/BookingScheduleManager.php";

$escape = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
};

$positiveInteger = static function ($value): ?int {
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $integer = filter_var($value, FILTER_VALIDATE_INT);
    return $integer !== false && $integer > 0 ? $integer : null;
};
// preserve the current movie and date while changing a booking choice
$bookingUrl = static function (array $changes): string {
    $parameters = array_merge($_GET, $changes);
    return "book-tickets.php?" . http_build_query($parameters);
};

$today = new DateTimeImmutable("today");
$bookingDays = [];
// show the next six days
for ($dayOffset = 1; $dayOffset <= 6; $dayOffset++) {
    $bookingDays[] = $today->modify("+{$dayOffset} days");
}

$movieId = $positiveInteger($_GET["movie_id"] ?? null);
$firstBookingDate = $bookingDays[0]->format("Y-m-d");
$requestedDate = $_GET["date"] ?? $firstBookingDate;
$selectedDate = is_string($requestedDate) ? $requestedDate : "";
$validSelectedDate = false;
foreach ($bookingDays as $bookingDay) {
    if ($bookingDay->format("Y-m-d") === $selectedDate) {
        $validSelectedDate = true;
        break;
    }
}
if (!$validSelectedDate) {
    $selectedDate = $firstBookingDate;
}

// read the selected showtime and prepare empty data for a page with no selection yet
$selectedScreeningId = $positiveInteger($_GET["screening_id"] ?? null);
$movies = [];
$screenings = [];
$ticketPrices = [];
$seats = [];
$screeningCounts = [];
$selectedScreening = null;
$pageError = "";
$scheduleAvailable = true;
$schedule = new BookingScheduleManager($conn);

try {
    // load movie options
    $movies = $schedule->getMovies();

    if ($movieId === null && $movies !== []) {
        $movieId = (int) $movies[0]["movie_id"];
    }

    if ($movieId !== null) {
        $movieExists = false;
        foreach ($movies as $movieOption) {
            if ((int) $movieOption["movie_id"] === $movieId) {
                $movieExists = true;
                break;
            }
        }

        if (!$movieExists) {
            $movieId = null;
            $pageError = "Choose a movie from the list.";
        }
    }

    if ($movieId !== null) {
        $screeningCounts = $schedule->getScreeningCounts(
            $movieId,
            $bookingDays[0]->format("Y-m-d"),
            $bookingDays[count($bookingDays) - 1]->format("Y-m-d")
        );
        $screenings = $schedule->getScreenings($movieId, $selectedDate);
    }

    if ($selectedScreeningId !== null && $movieId !== null) {
        $selectedScreening = $schedule->findScreening(
            $selectedScreeningId,
            $movieId,
            $selectedDate
        );
        if ($selectedScreening === null) {
            $pageError = "That showtime is no longer available. Choose another showtime.";
        } else {
            $ticketPrices = $schedule->getTicketPrices($selectedScreeningId);
            $seats = $schedule->getSeats(
                $selectedScreeningId,
                (int) $selectedScreening["hall_id"]
            );
        }
    }
} catch (mysqli_sql_exception $exception) {
    error_log("Booking schedule could not be loaded: " . $exception->getMessage());
    $pageError = "The booking schedule is not ready yet. Set up the booking tables and add screenings before taking bookings.";
    $scheduleAvailable = false;
}

$seatsByRow = [];
foreach ($seats as $seat) {
    $seatsByRow[$seat["row_number"]][] = $seat;
}
$seatColumnCount = $seatsByRow === []
    ? 0
    : max(array_map("count", $seatsByRow));
?>
<?php include __DIR__ . "/header.php"; ?>

<main class="mx-auto w-[min(100%-2rem,1800px)] py-8 sm:w-[min(100%-3rem,1800px)] sm:py-12 lg:py-16">
    <section class="mb-7">
        <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#ef5552]">Mystery Cinema · Reservations</p>
        <h1 class="text-3xl font-extrabold tracking-[-0.045em] sm:text-4xl lg:text-5xl">Book your seats</h1>
        <p class="mt-2 text-sm text-[#a6a6a6] sm:text-base">Choose a movie, showtime and seats for your visit.</p>
    </section>

    <?php if ($pageError !== ""): ?>
        <p class="mb-6 rounded-xl border border-red-400/20 bg-red-950/30 p-4 text-sm text-red-100" role="alert"><?= $escape($pageError) ?></p>
    <?php endif; ?>

    <form class="mb-6 flex flex-col gap-3 rounded-2xl border border-white/[0.07] bg-[#191919] p-4 sm:flex-row sm:items-center sm:gap-5 sm:p-5" action="book-tickets.php" method="get">
        <label class="shrink-0 text-sm font-bold" for="booking-movie">Movie</label>
        <select class="h-12 w-full rounded-xl border border-white/10 bg-[#242424] px-4 text-sm text-background outline-none transition focus:border-red-400/60 sm:max-w-md" id="booking-movie" name="movie_id" <?= $movies === [] ? "disabled" : "" ?>>
            <?php if ($movies === []): ?>
                <option value="">No movies available</option>
            <?php else: ?>
                <?php foreach ($movies as $movieOption): ?>
                    <option value="<?= (int) $movieOption["movie_id"] ?>" <?= (int) $movieOption["movie_id"] === $movieId ? "selected" : "" ?>>
                        <?= $escape($movieOption["movie_title"]) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <input type="hidden" name="date" value="<?= $escape($selectedDate) ?>">
        <noscript><button class="rounded-lg bg-accent px-4 py-2 text-sm font-bold text-white hover:bg-accent-dark" type="submit">Choose movie</button></noscript>
    </form>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(18rem,0.34fr)_minmax(0,0.66fr)] lg:gap-6">
        <aside class="grid min-w-0 content-start gap-5">
            <section class="rounded-2xl border border-white/[0.06] bg-[#1a1a1a] p-5 sm:p-6" aria-labelledby="booking-date-title">
                <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" id="booking-date-title"><span class="text-red-500" aria-hidden="true">▦</span> Select date</h2>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <?php foreach ($bookingDays as $bookingDay): ?>
                        <?php
                        $dayDate = $bookingDay->format("Y-m-d");
                        $dayScreeningCount = $screeningCounts[$dayDate] ?? 0;
                        ?>
                        <a
                            class="grid min-h-[76px] place-content-center gap-0.5 rounded-xl border border-white/[0.04] bg-[#242424] text-center text-sm text-[#a6a6a6] transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400<?= $dayDate === $selectedDate ? " border-accent bg-accent font-semibold text-white" : "" ?><?= $scheduleAvailable && $dayScreeningCount === 0 ? " opacity-70" : "" ?>"
                            href="<?= $escape($bookingUrl(["date" => $dayDate, "screening_id" => null])) ?>"
                            <?= $dayDate === $selectedDate ? 'aria-current="date"' : "" ?>
                        >
                            <span><?= $escape($bookingDay->format("D")) ?></span>
                            <span><?= $escape($bookingDay->format("d M")) ?></span>
                            <span class="mt-1 flex items-center justify-center gap-1.5 text-[11px] font-medium leading-none <?= !$scheduleAvailable ? "text-[#b7b7b7]" : ($dayScreeningCount > 0 ? "text-emerald-300" : "text-[#929292]") ?>">
                                <span class="h-1.5 w-1.5 rounded-full <?= !$scheduleAvailable ? "bg-[#777]" : ($dayScreeningCount > 0 ? "bg-emerald-400" : "bg-[#777]") ?>" aria-hidden="true"></span>
                                <?php if (!$scheduleAvailable): ?>
                                    Schedule unavailable
                                <?php elseif ($movieId === null): ?>
                                    Select a movie
                                <?php elseif ($dayScreeningCount > 0): ?>
                                    <?= $dayScreeningCount ?> <?= $dayScreeningCount === 1 ? "showtime" : "showtimes" ?>
                                <?php else: ?>
                                    No screenings
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-white/[0.06] bg-[#1a1a1a] p-5 sm:p-6" aria-labelledby="booking-showtimes-title">
                <h2 class="flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" id="booking-showtimes-title"><span class="text-red-500" aria-hidden="true">◷</span> Select showtime</h2>
                <?php if ($movieId === null): ?>
                    <p class="mt-4 text-sm leading-6 text-[#929292]">Choose a movie to see its showtimes.</p>
                <?php elseif ($screenings === []): ?>
                    <p class="mt-4 text-sm leading-6 text-[#929292]">No showtimes are scheduled for this movie on this date.</p>
                <?php else: ?>
                    <div class="mt-4 grid gap-2">
                        <?php foreach ($screenings as $screening): ?>
                            <?php $screeningId = (int) $screening["screening_id"]; ?>
                            <a
                                class="flex min-h-16 items-center justify-between gap-3 rounded-xl border border-white/[0.04] bg-[#242424] px-4 py-3 text-background transition hover:border-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400<?= $screeningId === $selectedScreeningId ? " border-accent bg-accent text-white" : "" ?>"
                                href="<?= $escape($bookingUrl(["screening_id" => $screeningId])) ?>"
                                <?= $screeningId === $selectedScreeningId ? 'aria-current="true"' : "" ?>
                            >
                                <span class="grid">
                                    <strong class="text-base"><?= $escape(date("H:i", strtotime($screening["start_time"]))) ?></strong>
                                    <span class="mt-0.5 text-xs text-[#b7b7b7]"><?= $escape($screening["hall_name"]) ?></span>
                                </span>
                                <span class="text-right text-xs text-[#c5c5c5]"><?= (int) $screening["seats_left"] ?> seats left</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="rounded-2xl border border-white/[0.06] bg-[#1a1a1a] p-5 sm:p-6" aria-labelledby="booking-tickets-title">
                <h2 class="text-sm font-extrabold uppercase tracking-wide" id="booking-tickets-title">Tickets</h2>
                <?php if ($selectedScreening === null): ?>
                    <p class="mt-4 text-sm leading-6 text-[#929292]">Choose a showtime to see ticket types and prices.</p>
                <?php elseif ($ticketPrices === []): ?>
                    <p class="mt-4 text-sm leading-6 text-[#929292]">Ticket prices have not been set for this showtime.</p>
                <?php else: ?>
                    <div class="mt-3">
                        <?php foreach ($ticketPrices as $ticket): ?>
                            <?php
                            $ageText = strtolower($ticket["ticket_type_name"]) === "child"
                                ? "Under 12"
                                : ($ticket["min_age"] === null
                                    ? "All ages"
                                    : (int) $ticket["min_age"] . "+");
                            $ticketPrice = (float) $ticket["ticket_price"];
                            $ticketPriceText = number_format(
                                $ticketPrice,
                                $ticketPrice === floor($ticketPrice) ? 0 : 2,
                                ".",
                                ","
                            );
                            ?>
                            <div
                                class="flex min-h-[76px] items-center justify-between gap-4 border-b border-white/[0.08]"
                                data-ticket
                                data-ticket-id="<?= (int) $ticket["ticket_type_id"] ?>"
                                data-price="<?= $escape((string) $ticketPrice) ?>"
                            >
                                <div class="min-w-0">
                                    <strong class="block text-sm font-semibold"><?= $escape($ticket["ticket_type_name"]) ?></strong>
                                    <span class="mt-1 block text-xs text-[#999]"><?= $escape($ageText) ?> · <?= $escape($ticketPriceText) ?> DKK</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-3" aria-label="<?= $escape($ticket["ticket_type_name"]) ?> ticket quantity">
                                    <button class="grid size-9 place-items-center rounded-full border border-white/[0.07] bg-[#242424] text-lg text-white hover:bg-[#333] disabled:cursor-not-allowed disabled:opacity-40" type="button" data-quantity-change="-1" aria-label="Remove one <?= $escape($ticket["ticket_type_name"]) ?> ticket">−</button>
                                    <output class="min-w-4 text-center text-sm font-bold" data-quantity>0</output>
                                    <button class="grid size-9 place-items-center rounded-full border border-white/[0.07] bg-[#242424] text-lg text-white hover:bg-[#333] disabled:cursor-not-allowed disabled:opacity-40" type="button" data-quantity-change="1" aria-label="Add one <?= $escape($ticket["ticket_type_name"]) ?> ticket">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between gap-3 pt-5 text-sm">
                        <span>Total tickets: <strong id="booking-ticket-count">0</strong></span>
                        <strong class="text-lg" id="booking-total-price">0.00 DKK</strong>
                    </div>
                <?php endif; ?>
            </section>
        </aside>

        <section class="grid min-w-0 content-start gap-5">
            <section class="min-w-0 rounded-2xl border border-white/[0.06] bg-[#1a1a1a] p-5 sm:p-7" aria-labelledby="booking-seats-title">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-baseline gap-2.5">
                        <h2 class="text-sm font-extrabold uppercase tracking-wide" id="booking-seats-title">Choose your seats</h2>
                        <span class="text-sm text-[#929292]" id="booking-seat-count">(0 selected)</span>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-[#929292]" aria-label="Seat legend">
                        <span class="inline-flex items-center gap-2"><i class="size-3 rounded bg-[#303030]"></i> Available</span>
                        <span class="inline-flex items-center gap-2"><i class="size-3 rounded bg-accent"></i> Selected</span>
                        <span class="inline-flex items-center gap-2"><i class="size-3 rounded bg-[#252525]"></i> Occupied</span>
                    </div>
                </div>

                <?php if ($selectedScreening === null): ?>
                    <div class="mt-6 grid min-h-56 place-items-center rounded-xl border border-dashed border-white/10 p-6 text-center text-sm text-[#929292]">Choose a showtime to view the seat map.</div>
                <?php elseif ($seatsByRow === []): ?>
                    <div class="mt-6 grid min-h-56 place-items-center rounded-xl border border-dashed border-white/10 p-6 text-center text-sm text-[#929292]">There are no active seats configured for this hall.</div>
                <?php else: ?>
                    <div class="mt-7 grid min-h-10 place-items-center rounded-lg border border-[#444] bg-gradient-to-b from-[#303030] to-[#282828] text-xs uppercase tracking-[0.2em] text-[#999]">Screen</div>
                    <div class="mt-8 grid gap-2.5 sm:mt-12" style="--seat-columns: <?= $seatColumnCount ?>">
                        <?php foreach ($seatsByRow as $rowNumber => $rowSeats): ?>
                            <div class="grid grid-cols-[16px_minmax(0,1fr)_16px] items-center gap-1.5 sm:grid-cols-[20px_minmax(0,1fr)_20px] sm:gap-2.5">
                                <span class="text-center text-xs text-[#999]"><?= $escape((string) $rowNumber) ?></span>
                                <div class="grid grid-cols-[repeat(var(--seat-columns),minmax(0,1fr))] gap-0.5 sm:gap-1.5">
                                    <?php foreach ($rowSeats as $seat): ?>
                                        <?php
                                        $isOccupied = (int) $seat["is_occupied"] === 1;
                                        $seatLabel = (string) $seat["row_number"] . (int) $seat["seat_number"];
                                        ?>
                                        <button
                                            class="aspect-square min-w-0 rounded-[4px] border border-transparent bg-[#303030] text-[9px] text-[#cfcfcf] transition hover:-translate-y-0.5 hover:bg-[#4a4a4a] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-red-400 disabled:cursor-not-allowed disabled:hover:translate-y-0 sm:rounded-md sm:text-xs<?= $isOccupied ? " bg-[#252525] text-[#777] hover:bg-[#252525]" : " cursor-pointer" ?>"
                                            type="button"
                                            data-seat
                                            data-seat-id="<?= (int) $seat["seat_id"] ?>"
                                            aria-label="Seat <?= $escape($seatLabel) ?><?= $isOccupied ? ", occupied" : ", available" ?>"
                                            aria-pressed="false"
                                            <?= $isOccupied ? "disabled" : "" ?>
                                        ><?= (int) $seat["seat_number"] ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <span class="text-center text-xs text-[#999]"><?= $escape((string) $rowNumber) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="grid justify-items-stretch gap-2 sm:justify-items-end">
                <p class="min-h-5 text-right text-sm text-[#bcbcbc]" id="booking-checkout-status" role="status" aria-live="polite"></p>
                <button class="min-h-14 rounded-xl bg-accent px-7 text-sm font-extrabold text-white shadow-lg shadow-red-950/30 transition hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-red-300 disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-64" id="booking-checkout-button" type="button" disabled>
                    Continue to checkout
                </button>
            </div>
        </section>
    </div>
</main>

<script src="./js/book-tickets.js" defer></script>
<?php include __DIR__ . "/footer.php"; ?>
