function updateBookingTimers() {

    const now =
        Math.floor(
            Date.now() / 1000
        );


    document
        .querySelectorAll(
            ".upcoming-card"
        )
        .forEach(card => {

            const start =
                parseInt(
                    card.dataset.start
                );

            const end =
                parseInt(
                    card.dataset.end
                );


            const label =
                card.querySelector(
                    ".timer-label"
                );

            const timer =
                card.querySelector(
                    ".timer"
                );


            let seconds;


            if (now < start) {

                seconds =
                    start - now;

                label.textContent =
                    "STARTS IN";

                card.classList.remove(
                    "active-booking"
                );

            } else if (
                now >= start &&
                now < end
            ) {

                seconds =
                    end - now;

                label.textContent =
                    "TIME REMAINING";

                card.classList.add(
                    "active-booking"
                );

            } else {

                label.textContent =
                    "COMPLETED";

                timer.textContent =
                    "00:00:00";

                card.classList.remove(
                    "active-booking"
                );

                return;
            }


            const hours =
                Math.floor(
                    seconds / 3600
                );

            const minutes =
                Math.floor(
                    (seconds % 3600) / 60
                );

            const secs =
                seconds % 60;


            timer.textContent =
                String(hours).padStart(2, "0") +
                ":" +
                String(minutes).padStart(2, "0") +
                ":" +
                String(secs).padStart(2, "0");

        });
}


updateBookingTimers();

setInterval(
    updateBookingTimers,
    1000
);
