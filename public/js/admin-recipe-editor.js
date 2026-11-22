/* CrispyCloud: page interactions. Keep the Blade view markup-only. */
(function () {
    const noEffect = document.getElementById("air-no-effect-checkbox");
    const amounts = document.querySelectorAll(
        "#air-recipe-table [data-stock-amount]",
    );
    const search = document.getElementById("air-ingredient-search");
    if (search)
        search.addEventListener("input", () => {
            const term = search.value.trim().toLocaleLowerCase();
            document
                .querySelectorAll("#air-recipe-table tbody tr")
                .forEach((row) => {
                    row.hidden = !row.textContent
                        .toLocaleLowerCase()
                        .includes(term);
                });
        });
    if (!noEffect) return;
    function sync() {
        if (noEffect.checked)
            amounts.forEach((input) => {
                input.value = "0";
                input.disabled = true;
            });
        else
            amounts.forEach((input) => {
                input.disabled = false;
            });
    }
    noEffect.addEventListener("change", sync);
    if (noEffect.checked) sync();
})();
