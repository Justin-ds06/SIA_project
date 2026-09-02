document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("bookingForm");

    const sportCards =
        document.querySelectorAll(".sport-card");

    const courtCards =
        document.querySelectorAll(".court-card");

    const sportInput =
        document.getElementById("sport_id");

    const courtInput =
        document.getElementById("court_id");

    const dateInput =
        document.getElementById("booking_date");

    const timeInput =
        document.getElementById("start_time");

    const durationInputs =
        document.querySelectorAll(
            'input[name="duration"]'
        );

    const paymentInputs =
        document.querySelectorAll(
            'input[name="payment_method"]'
        );

    const courtMessage =
        document.getElementById("court-message");

    const availabilityMessage =
        document.getElementById(
            "availability-message"
        );

    const qrPayment =
        document.getElementById("qr-payment");

    const cashBreakdown =
        document.getElementById(
            "cash-breakdown"
        );

    const paymentAmount =
        document.getElementById(
            "payment-amount"
        );

    const cashTotal =
        document.getElementById(
            "cash-total"
        );

    const cashDeposit =
        document.getElementById(
            "cash-deposit"
        );

    const cashBalance =
        document.getElementById(
            "cash-balance"
        );

    const summarySport =
        document.getElementById(
            "summary-sport"
        );

    const summaryCourt =
        document.getElementById(
            "summary-court"
        );

    const summaryDate =
        document.getElementById(
            "summary-date"
        );

    const summaryTime =
        document.getElementById(
            "summary-time"
        );

    const summaryDuration =
        document.getElementById(
            "summary-duration"
        );

    const summaryPayment =
        document.getElementById(
            "summary-payment"
        );

    const summaryTotal =
        document.getElementById(
            "summary-total"
        );

    const summaryPay =
        document.getElementById(
            "summary-pay"
        );

    const confirmButton =
        document.getElementById(
            "confirm-button"
        );


    let selectedPrice = 0;
    let selectedSportName = "";
    let selectedCourtName = "";


    /*
    |--------------------------------------------------------------------------
    | FORMAT MONEY
    |--------------------------------------------------------------------------
    */

    function money(amount) {

        return "₱" + Number(amount).toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SPORT SELECTION
    |--------------------------------------------------------------------------
    */

    sportCards.forEach(function (card) {

        card.addEventListener("click", function () {

            sportCards.forEach(function (item) {
                item.classList.remove("selected");
            });

            courtCards.forEach(function (item) {
                item.classList.remove("selected");
            });

            card.classList.add("selected");

            const sportId =
                card.dataset.sportId;

            selectedPrice =
                parseFloat(
                    card.dataset.price
                ) || 0;

            selectedSportName =
                card.querySelector(
                    "h3"
                ).textContent.trim();

            sportInput.value =
                sportId;

            courtInput.value = "";

            selectedCourtName = "";

            courtCards.forEach(function (court) {

                if (
                    court.dataset.sportId ===
                    sportId
                ) {

                    court.classList.remove(
                        "hidden"
                    );

                } else {

                    court.classList.add(
                        "hidden"
                    );
                }

            });

            courtMessage.textContent =
                "Select an available court.";

            updateSummary();

            calculatePayment();

            checkAvailability();
        });
    });


    /*
    |--------------------------------------------------------------------------
    | COURT SELECTION
    |--------------------------------------------------------------------------
    */

    courtCards.forEach(function (card) {

        card.addEventListener("click", function () {

            if (
                card.classList.contains(
                    "hidden"
                )
            ) {
                return;
            }

            courtCards.forEach(function (item) {
                item.classList.remove(
                    "selected"
                );
            });

            card.classList.add("selected");

            courtInput.value =
                card.dataset.courtId;

            selectedCourtName =
                card.querySelector(
                    "h3"
                ).textContent.trim();

            courtMessage.textContent =
                "Court selected.";

            updateSummary();

            checkAvailability();
        });
    });


    /*
    |--------------------------------------------------------------------------
    | DURATION
    |--------------------------------------------------------------------------
    */

    durationInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            function () {

                updateSummary();

                calculatePayment();

                checkAvailability();
            }
        );
    });


    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHOD
    |--------------------------------------------------------------------------
    */

    paymentInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            function () {

                updatePaymentDisplay();

                updateSummary();
            }
        );
    });


    /*
    |--------------------------------------------------------------------------
    | DATE / TIME
    |--------------------------------------------------------------------------
    */

    dateInput.addEventListener(
        "change",
        function () {

            checkAvailability();
            updateSummary();
        }
    );


    timeInput.addEventListener(
        "change",
        function () {

            checkAvailability();
            updateSummary();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CALCULATE PAYMENT
    |--------------------------------------------------------------------------
    */

    function calculatePayment() {

        const durationInput =
            document.querySelector(
                'input[name="duration"]:checked'
            );

        if (!durationInput) {
            return;
        }

        const duration =
            parseInt(
                durationInput.value
            );

        const total =
            selectedPrice * duration;

        const payment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );

        let amountToPay = 0;

        if (payment) {

            if (
                payment.value ===
                "GCash"
            ) {

                amountToPay = total;

            } else if (
                payment.value ===
                "Cash"
            ) {

                amountToPay =
                    total * 0.40;
            }
        }

        paymentAmount.textContent =
            money(amountToPay);

        cashTotal.textContent =
            money(total);

        cashDeposit.textContent =
            money(total * 0.40);

        cashBalance.textContent =
            money(total * 0.60);

        summaryTotal.textContent =
            money(total);

        summaryPay.textContent =
            money(amountToPay);
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT DISPLAY
    |--------------------------------------------------------------------------
    */

    function updatePaymentDisplay() {

        const payment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );

        if (!payment) {

            qrPayment.classList.remove(
                "show"
            );

            cashBreakdown.style.display =
                "none";

            return;
        }

        /*
        | Both GCash and Cash require GCash
        | because Cash requires 40% GCash
        | downpayment.
        */

        qrPayment.classList.add(
            "show"
        );

        if (
            payment.value ===
            "Cash"
        ) {

            cashBreakdown.style.display =
                "block";

        } else {

            cashBreakdown.style.display =
                "none";
        }

        calculatePayment();
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    function updateSummary() {

        summarySport.textContent =
            selectedSportName ||
            "Not selected";

        summaryCourt.textContent =
            selectedCourtName ||
            "Not selected";

        summaryDate.textContent =
            dateInput.value ||
            "Not selected";

        if (timeInput.value) {

            const parts =
                timeInput.value.split(":");

            let hour =
                parseInt(parts[0]);

            const minute =
                parts[1];

            const suffix =
                hour >= 12
                    ? "PM"
                    : "AM";

            hour =
                hour % 12 || 12;

            summaryTime.textContent =
                hour +
                ":" +
                minute +
                " " +
                suffix;

        } else {

            summaryTime.textContent =
                "Not selected";
        }


        const durationInput =
            document.querySelector(
                'input[name="duration"]:checked'
            );

        if (durationInput) {

            const duration =
                parseInt(
                    durationInput.value
                );

            summaryDuration.textContent =
                duration +
                (duration === 1
                    ? " Hour"
                    : " Hours");
        }


        const payment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );

        summaryPayment.textContent =
            payment
                ? payment.value
                : "Not selected";

        calculatePayment();
    }


    /*
    |--------------------------------------------------------------------------
    | REAL-TIME AVAILABILITY
    |--------------------------------------------------------------------------
    */

    let availabilityTimer = null;


    function checkAvailability() {

        clearTimeout(
            availabilityTimer
        );

        availabilityTimer =
            setTimeout(
                performAvailabilityCheck,
                300
            );
    }


    function performAvailabilityCheck() {

        const courtId =
            courtInput.value;

        const date =
            dateInput.value;

        const time =
            timeInput.value;

        const durationInput =
            document.querySelector(
                'input[name="duration"]:checked'
            );

        if (
            !courtId ||
            !date ||
            !time ||
            !durationInput
        ) {

            availabilityMessage.style.display =
                "none";

            confirmButton.disabled =
                false;

            return;
        }

        const duration =
            durationInput.value;


        availabilityMessage.textContent =
            "Checking court availability...";

        availabilityMessage.className =
            "availability-message available";


        fetch(
            "check_availability.php" +
            "?court_id=" +
            encodeURIComponent(courtId) +
            "&booking_date=" +
            encodeURIComponent(date) +
            "&start_time=" +
            encodeURIComponent(time) +
            "&duration=" +
            encodeURIComponent(duration)
        )
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {

            if (data.available) {

                availabilityMessage.textContent =
                    "✓ This court is available for your selected time.";

                availabilityMessage.className =
                    "availability-message available";

                confirmButton.disabled =
                    false;

            } else {

                availabilityMessage.textContent =
                    "✕ " +
                    (
                        data.message ||
                        "This court is not available."
                    );

                availabilityMessage.className =
                    "availability-message unavailable";

                confirmButton.disabled =
                    true;
            }

        })
        .catch(function () {

            availabilityMessage.textContent =
                "Unable to check availability. Please try again.";

            availabilityMessage.className =
                "availability-message unavailable";

            confirmButton.disabled =
                false;
        });
    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT DOUBLE SUBMIT
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        "submit",
        function (event) {

            if (
                confirmButton.disabled
            ) {

                event.preventDefault();

                return;
            }

            confirmButton.disabled =
                true;

            confirmButton.textContent =
                "Processing...";
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATE
    |--------------------------------------------------------------------------
    */

    courtCards.forEach(function (card) {
        card.classList.add("hidden");
    });

    qrPayment.classList.remove(
        "show"
    );

    cashBreakdown.style.display =
        "none";

    updateSummary();

});