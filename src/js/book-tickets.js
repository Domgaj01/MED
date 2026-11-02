const moviePicker = document.getElementById("booking-movie");
if (moviePicker) {
    moviePicker.addEventListener("change", () => {
        const destination = new URL(window.location.href);
        destination.searchParams.set("movie_id", moviePicker.value);
        destination.searchParams.delete("screening_id");
        window.location.assign(destination);
    });
}

const ticketRows = Array.from(document.querySelectorAll("[data-ticket]"));
const availableSeats = Array.from(document.querySelectorAll("[data-seat]:not(:disabled)"));
const selectedSeats = new Set();
const ticketCountOutput = document.getElementById("booking-ticket-count");
const ticketTotalOutput = document.getElementById("booking-total-price");
const seatCountOutput = document.getElementById("booking-seat-count");
const checkoutButton = document.getElementById("booking-checkout-button");
const checkoutStatus = document.getElementById("booking-checkout-status");

const updateBookingSummary = () => {
    const totalTickets = ticketRows.reduce((total, row) => {
        return total + Number(row.querySelector("[data-quantity]").textContent);
    }, 0);
    const totalPrice = ticketRows.reduce((total, row) => {
        const quantity = Number(row.querySelector("[data-quantity]").textContent);
        return total + quantity * Number(row.dataset.price);
    }, 0);

    if (ticketCountOutput) {
        ticketCountOutput.textContent = String(totalTickets);
    }
    if (ticketTotalOutput) {
        const formattedTotal = totalPrice.toLocaleString("da-DK", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        ticketTotalOutput.textContent = `${formattedTotal} DKK`;
    }
    if (seatCountOutput) {
        seatCountOutput.textContent = `(${selectedSeats.size} selected)`;
    }

    for (const row of ticketRows) {
        const quantity = Number(row.querySelector("[data-quantity]").textContent);
        const changeButtons = row.querySelectorAll("[data-quantity-change]");
        for (const button of changeButtons) {
            const change = Number(button.dataset.quantityChange);
            button.disabled = change < 0
                ? quantity === 0
                : totalTickets >= Math.min(12, availableSeats.length);
        }
    }

    if (checkoutButton) {
        checkoutButton.disabled =
            totalTickets === 0 ||
            selectedSeats.size !== totalTickets ||
            ticketRows.length === 0;
    }
};

for (const row of ticketRows) {
    for (const button of row.querySelectorAll("[data-quantity-change]")) {
        button.addEventListener("click", () => {
            const quantity = row.querySelector("[data-quantity]");
            const nextQuantity = Number(quantity.textContent) + Number(button.dataset.quantityChange);
            quantity.textContent = String(Math.max(0, nextQuantity));
            if (checkoutStatus) {
                checkoutStatus.textContent = "";
            }
            updateBookingSummary();
        });
    }
}

for (const seat of availableSeats) {
    seat.addEventListener("click", () => {
        const seatId = seat.dataset.seatId;
        const ticketCount = ticketRows.reduce((total, row) => {
            return total + Number(row.querySelector("[data-quantity]").textContent);
        }, 0);

        if (selectedSeats.has(seatId)) {
            selectedSeats.delete(seatId);
            seat.classList.remove("bg-accent", "text-white");
            seat.setAttribute("aria-pressed", "false");
        } else if (selectedSeats.size < ticketCount) {
            selectedSeats.add(seatId);
            seat.classList.add("bg-accent", "text-white");
            seat.setAttribute("aria-pressed", "true");
        }

        if (checkoutStatus) {
            checkoutStatus.textContent = "";
        }
        updateBookingSummary();
    });
}

if (checkoutButton && checkoutStatus) {
    checkoutButton.addEventListener("click", () => {
        checkoutStatus.textContent =
            "Your selection is ready. The checkout and booking confirmation step is not connected yet.";
    });
}

updateBookingSummary();
