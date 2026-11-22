/* Print only when an administrator activates the receipt button. */
document.querySelectorAll("[data-print-receipt]").forEach((button) => {
    button.addEventListener("click", () => window.print());
});
