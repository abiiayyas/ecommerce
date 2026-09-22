const checkoutForm = document.querySelector("[data-landing-checkout]");

const pixelId = document.querySelector('meta[name="meta-pixel-id"]')?.content;
const viewEventId = document.querySelector('meta[name="meta-event-id"]')?.content;

if (pixelId) {
    ((windowObject, documentObject, tagName, source, node, firstScript) => {
        if (windowObject.fbq) return;
        node = windowObject.fbq = (...args) => {
            node.callMethod ? node.callMethod(...args) : node.queue.push(args);
        };
        if (!windowObject._fbq) windowObject._fbq = node;
        node.push = node;
        node.loaded = true;
        node.version = "2.0";
        node.queue = [];
        firstScript = documentObject.createElement(tagName);
        firstScript.async = true;
        firstScript.src = source;
        documentObject.head.appendChild(firstScript);
    })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");

    window.fbq("init", pixelId);
    window.fbq("track", "PageView");
    window.fbq("track", "ViewContent", {}, { eventID: viewEventId });
}

if (checkoutForm) {
    const areaSearch = checkoutForm.querySelector("[data-area-search]");
    const areaResults = checkoutForm.querySelector("[data-area-results]");
    const areaId = checkoutForm.querySelector("[data-area-id]");
    const areaName = checkoutForm.querySelector("[data-area-name]");
    const productFlat = checkoutForm.querySelector("[data-product-flat]");
    const quantity = checkoutForm.querySelector("[data-quantity]");
    const rateId = checkoutForm.querySelector("[data-rate-id]");
    const rateResults = checkoutForm.querySelector("[data-rate-results]");
    const submitOrder = checkoutForm.querySelector("[data-submit-order]");
    const marketingEventId = checkoutForm.querySelector("[data-marketing-event-id]");
    const price = document.querySelector("#landing-price");
    let areaTimer;

    const csrfToken = checkoutForm.querySelector('input[name="_token"]').value;
    const currency = new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    });

    const resetRate = () => {
        rateId.value = "";
        rateResults.replaceChildren();
        submitOrder.disabled = true;
    };

    const updatePrice = () => {
        const selected = productFlat.selectedOptions[0];
        price.textContent = currency.format(Number(selected.dataset.price) || 0);
        resetRate();
    };

    productFlat.addEventListener("change", updatePrice);
    quantity.addEventListener("change", resetRate);
    checkoutForm.querySelectorAll('input[name="payment_mode"]').forEach((input) => {
        input.addEventListener("change", resetRate);
    });

    areaSearch.addEventListener("input", () => {
        window.clearTimeout(areaTimer);
        areaId.value = "";
        areaName.value = "";
        resetRate();
        areaResults.replaceChildren();
        areaResults.classList.add("hidden");

        if (areaSearch.value.trim().length < 3) return;

        areaTimer = window.setTimeout(async () => {
            const url = new URL(checkoutForm.dataset.areaUrl, window.location.origin);
            url.searchParams.set("query", areaSearch.value.trim());
            url.searchParams.set("product_flat_id", productFlat.value);

            try {
                const response = await fetch(url, { headers: { Accept: "application/json" } });
                if (!response.ok) throw new Error("Area lookup failed");
                const { areas = [] } = await response.json();

                for (const area of areas) {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = "block min-h-11 w-full border-b border-stone-800 px-3 py-2 text-left text-sm hover:bg-stone-800 focus:bg-stone-800 focus:outline-none";
                    button.textContent = [area.name, area.city, area.province, area.postal_code].filter(Boolean).join(", ");
                    button.addEventListener("click", () => {
                        areaId.value = area.id;
                        areaName.value = button.textContent;
                        areaSearch.value = button.textContent;
                        areaResults.classList.add("hidden");
                        resetRate();
                    });
                    areaResults.appendChild(button);
                }

                areaResults.classList.toggle("hidden", areas.length === 0);
            } catch {
                areaResults.textContent = "Area tidak dapat dimuat. Coba kembali.";
                areaResults.classList.remove("hidden");
            }
        }, 350);
    });

    checkoutForm.querySelector("[data-load-rates]").addEventListener("click", async () => {
        resetRate();

        if (!areaId.value) {
            rateResults.textContent = "Pilih area tujuan terlebih dahulu.";
            return;
        }

        const paymentMode = checkoutForm.querySelector('input[name="payment_mode"]:checked')?.value;
        rateResults.textContent = "Menghitung ongkir…";

        try {
            const response = await fetch(checkoutForm.dataset.rateUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    product_flat_id: Number(productFlat.value),
                    quantity: Number(quantity.value),
                    destination_area_id: areaId.value,
                    payment_mode: paymentMode,
                }),
            });
            if (!response.ok) throw new Error("Rate lookup failed");
            const { rates = [] } = await response.json();
            rateResults.replaceChildren();

            for (const rate of rates) {
                const label = document.createElement("label");
                label.className = "flex min-h-14 cursor-pointer items-center justify-between gap-4 border border-stone-700 p-3 hover:border-stone-400";
                const input = document.createElement("input");
                input.type = "radio";
                input.name = "rate_choice";
                input.value = rate.id;
                input.addEventListener("change", () => {
                    rateId.value = rate.id;
                    submitOrder.disabled = false;
                });
                const description = document.createElement("span");
                description.className = "grow text-sm";
                description.textContent = `${rate.courier} ${rate.service}${rate.description ? ` · ${rate.description}` : ""}`;
                const amount = document.createElement("strong");
                amount.textContent = currency.format(rate.price);
                label.append(input, description, amount);
                rateResults.appendChild(label);
            }

            if (rates.length === 0) rateResults.textContent = "Tidak ada layanan pengiriman yang tersedia.";
        } catch {
            rateResults.textContent = "Ongkir tidak dapat dihitung. Coba kembali.";
        }
    });

    checkoutForm.addEventListener("submit", (event) => {
        if (!rateId.value) {
            event.preventDefault();
            rateResults.textContent = "Pilih layanan pengiriman sebelum melanjutkan.";
            return;
        }

        const eventId = window.crypto?.randomUUID?.();
        marketingEventId.value = eventId ?? "";
        if (window.fbq) {
            window.fbq("track", "InitiateCheckout", {
                currency: "IDR",
                value: Number(productFlat.selectedOptions[0].dataset.price) * Number(quantity.value),
            }, eventId ? { eventID: eventId } : undefined);
        }
    });
}
