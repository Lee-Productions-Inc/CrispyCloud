/* CrispyCloud — business spending and report presentation. */
(() => {
    "use strict";

    function initSpendingDescription() {
        const type = document.getElementById("spending-type");
        const other = document.getElementById("spending-other");
        if (!type || !other) return;

        const sync = () => {
            const custom = type.value === "Other";
            other.hidden = !custom;
            other.disabled = !custom;
            other.required = custom;
            // Exactly one active field submits to the existing description column.
            type.name = custom ? "" : "description";
            other.name = custom ? "description" : "";
        };

        type.addEventListener("change", sync);
        sync();
    }

    function initReportBars() {
        const setPercent = (selector, property) => {
            document.querySelectorAll(selector).forEach((element) => {
                const percent = Number(element.dataset.percent);
                if (!Number.isFinite(percent)) return;
                element.style[property] =
                    `${Math.max(0, Math.min(100, percent))}%`;
            });
        };
        setPercent("[data-report-bar-height]", "height");
        setPercent("[data-report-bar-width]", "width");
    }

    function init() {
        initSpendingDescription();
        initReportBars();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
