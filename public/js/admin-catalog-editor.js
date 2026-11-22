/* CrispyCloud: page interactions. Keep the Blade view markup-only. */
(function () {
    document.querySelectorAll("[data-extra-editor]").forEach((button) => {
        button.addEventListener("click", () => {
            const panel = document.getElementById(button.dataset.extraEditor);
            if (!panel) return;
            const opening = panel.hidden;
            document
                .querySelectorAll(".cp-ux-editor:not([hidden])")
                .forEach((other) => {
                    other.hidden = true;
                });
            document
                .querySelectorAll("[data-extra-editor]")
                .forEach((other) =>
                    other.setAttribute("aria-expanded", "false"),
                );
            panel.hidden = !opening;
            button.setAttribute("aria-expanded", opening ? "true" : "false");
        });
    });
})();
