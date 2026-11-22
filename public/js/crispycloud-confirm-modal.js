/* CrispyCloud Step 38 — Progressive enhancement for important form submissions.
   No server-side authorization, payment, or inventory rules are changed. */
(() => {
    "use strict";

    const approved = new WeakSet();
    let active = null;
    let modal = null;
    let titleEl, bodyEl, actionButton, cancelButton;

    function destination(form) {
        try {
            return new URL(form.action, document.baseURI).pathname.replace(
                /\/+$/,
                "",
            );
        } catch (_) {
            return "";
        }
    }

    function requestMethod(form) {
        const override = form.querySelector('input[name="_method"]');
        return (override ? override.value : form.method || "GET").toUpperCase();
    }

    function orderNumber(path) {
        const match = path.match(/\/(?:orders|pos)\/(\d+)(?:\/|$)/);
        return match ? `#CP-${match[1]}` : "this order";
    }

    function describe(form) {
        const path = destination(form);
        const method = requestMethod(form);

        if (form.classList.contains("a-os-status-form") && method === "PATCH") {
            const status = form.elements.namedItem("order_status")?.value;
            const order = orderNumber(path);
            if (status === "Delivered") {
                return {
                    title: `Mark order ${order} as Delivered?`,
                    message:
                        "Confirm the order has been delivered. This will record delivery and deduct inventory using the configured recipes. The status cannot be reversed.",
                    action: "Mark Delivered",
                    tone: "caution",
                };
            }
            if (status === "Cancelled") {
                return {
                    title: `Cancel order ${order}?`,
                    message:
                        "The order will be cancelled and cannot be restored. Check its payment status before continuing.",
                    action: "Cancel order",
                    tone: "danger",
                };
            }
            return null; // Ordinary status progress stays quick.
        }

        if (
            form.classList.contains("a-om-payment-form") &&
            method === "PATCH"
        ) {
            return {
                title: `Record payment for ${orderNumber(path)}?`,
                message:
                    "Only continue if the payment has actually been received or independently verified. This records a payment as Paid; it does not initiate a GCash transaction.",
                action: "Mark Paid",
                tone: "caution",
            };
        }

        if (/\/admin\/pos\/\d+\/complete$/.test(path) && method === "POST") {
            return {
                title: `Mark walk-in order ${orderNumber(path)} as Served?`,
                message:
                    "Confirm the prepared food has been handed to the customer. Inventory will be deducted once, subject to recipe and stock validation.",
                action: "Mark as Served",
                tone: "caution",
            };
        }

        if (
            /\/admin\/catalog\/addons\/\d+$/.test(path) &&
            method === "DELETE"
        ) {
            const label =
                form
                    .querySelector('button[type="submit"]')
                    ?.getAttribute("aria-label") || "this unused extra";
            return {
                title: "Delete this food extra?",
                message: `${label}. This cannot be undone. Food extras referenced by old orders remain protected by the server.`,
                action: "Delete extra",
                tone: "danger",
            };
        }

        if (
            form.classList.contains("ar-expense-void-form") &&
            method === "POST"
        ) {
            return {
                title: "Void this spending entry?",
                message:
                    "The entry will be excluded from spending totals. Its audit history will be kept.",
                action: "Void entry",
                tone: "danger",
            };
        }

        if (form.classList.contains("ai-adjust-form") && method === "PATCH") {
            if (form.elements.namedItem("direction")?.value !== "remove")
                return null;
            const quantity = form.elements.namedItem("quantity")?.value || "";
            const summary =
                form
                    .closest("details")
                    ?.querySelector("summary")
                    ?.textContent.trim() || "this ingredient";
            return {
                title: "Confirm stock deduction",
                message: `Record Stock Out of ${quantity} for ${summary.replace(/\s*[⌄▼]\s*$/, "")}? The balance and stock history will be updated.`,
                action: "Record Stock Out",
                tone: "caution",
            };
        }

        if (
            /\/admin\/business-capital\/\d+\/void$/.test(path) &&
            method === "POST"
        ) {
            return {
                title: "Void this capital entry?",
                message:
                    "The entry will be excluded from active monthly capital totals, while its history and correction reason are kept. This cannot be reversed.",
                action: "Void entry",
                tone: "danger",
            };
        }

        if (form.classList.contains("cco-cancel-form") && method === "PATCH") {
            return {
                title: `Cancel order ${orderNumber(path)}?`,
                message:
                    "This unpaid order will be cancelled and cannot be restored. If you already sent a GCash payment, contact the store instead.",
                action: "Cancel order",
                tone: "danger",
            };
        }

        if (
            /\/addresses\/\d+$/.test(path) &&
            method === "DELETE" &&
            !path.includes("/admin/")
        ) {
            return {
                title: "Remove this saved address?",
                message:
                    "This address will no longer be available for future checkouts. Existing orders will not be changed.",
                action: "Remove address",
                tone: "danger",
            };
        }

        return null;
    }

    function buildModal() {
        modal = document.createElement("dialog");
        modal.className = "cc-confirm-modal";
        modal.setAttribute("aria-labelledby", "cc-confirm-title");
        modal.setAttribute("aria-describedby", "cc-confirm-description");
        modal.innerHTML = `
            <div class="cc-confirm-panel">
                <div class="cc-confirm-mark" aria-hidden="true">!</div>
                <h2 id="cc-confirm-title"></h2>
                <p id="cc-confirm-description"></p>
                <div class="cc-confirm-actions">
                    <button type="button" class="cc-confirm-cancel" data-cc-cancel>Go back</button>
                    <button type="button" class="cc-confirm-accept" data-cc-accept>Confirm</button>
                </div>
            </div>`;
        document.body.appendChild(modal);
        titleEl = modal.querySelector("#cc-confirm-title");
        bodyEl = modal.querySelector("#cc-confirm-description");
        cancelButton = modal.querySelector("[data-cc-cancel]");
        actionButton = modal.querySelector("[data-cc-accept]");

        cancelButton.addEventListener("click", () => modal.close("cancel"));
        actionButton.addEventListener("click", () => {
            if (!active) return;
            const { form, submitter } = active;
            active = null;
            modal.close("accept");
            proceed(form, submitter);
        });
        modal.addEventListener("close", () => {
            if (active) {
                const trigger = active.submitter;
                active = null;
                if (trigger?.isConnected) trigger.focus();
            }
        });
        modal.addEventListener("click", (event) => {
            if (event.target === modal) modal.close("cancel");
        });
    }

    function proceed(form, submitter) {
        if (!form.isConnected) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        approved.add(form);
        try {
            if (typeof form.requestSubmit === "function") {
                if (submitter?.isConnected && submitter.form === form)
                    form.requestSubmit(submitter);
                else form.requestSubmit();
            } else {
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            approved.delete(form);
        }
    }

    function onSubmit(event) {
        const form = event.target;
        if (
            !(form instanceof HTMLFormElement) ||
            approved.has(form) ||
            event.defaultPrevented
        )
            return;
        const prompt = describe(form);
        if (!prompt) return;
        event.preventDefault();
        const submitter = event.submitter || null;
        if (!modal || typeof modal.showModal !== "function") {
            if (window.confirm(`${prompt.title}\n\n${prompt.message}`))
                proceed(form, submitter);
            return;
        }
        if (modal.open) return;
        active = { form, submitter };
        titleEl.textContent = prompt.title;
        bodyEl.textContent = prompt.message;
        actionButton.textContent = prompt.action;
        modal.dataset.tone = prompt.tone;
        modal.showModal();
        cancelButton.focus();
    }

    function init() {
        if (!document.body) return;
        buildModal();
        document.addEventListener("submit", onSubmit, true);
    }

    if (document.readyState === "loading")
        document.addEventListener("DOMContentLoaded", init, { once: true });
    else init();
})();
