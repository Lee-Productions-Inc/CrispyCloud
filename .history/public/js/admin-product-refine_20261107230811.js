(() => {
    'use strict';
    const category = document.getElementById('p-cat');
    const section = document.getElementById('ap-food-customizations');
    const note = document.getElementById('ap-drink-note');
    if (!category || !section || !note) return;
    function refresh() {
        const beverage = category.selectedOptions[0]?.dataset.isBeverage === '1';
        section.hidden = beverage;
        note.hidden = !beverage;
        section.querySelectorAll('input, textarea, select').forEach((field) => {
            field.disabled = beverage;
        });
    }
    category.addEventListener('change', refresh);
    refresh();
})();
