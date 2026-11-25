// Customer cart — only selected lines are sent to checkout.
(() => {
    const form = document.getElementById('cart-checkout-selection');
    if (!form) return;

    const checkboxes = [...document.querySelectorAll('[data-cart-select]')];
    const selectAll = document.querySelector('[data-select-all]');
    const subtotalNodes = document.querySelectorAll('[data-selected-subtotal]');
    const quantityNode = document.querySelector('[data-selected-quantity]');
    const checkoutButton = document.querySelector('[data-checkout-button]');
    const noSelectionMessage = document.querySelector('[data-cart-select-message]');
    const money = (cents) => `₱${(cents / 100).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})}`;

    function update() {
        const picked = checkboxes.filter((checkbox) => checkbox.checked);
        const quantity = picked.reduce((sum, el) => sum + Number(el.dataset.qty || 0), 0);
        const cents = picked.reduce((sum, el) => sum + Number(el.dataset.cents || 0), 0);
        subtotalNodes.forEach((node) => { node.textContent = money(cents); });
        if (quantityNode) quantityNode.textContent = String(quantity);
        if (checkoutButton) checkoutButton.disabled = picked.length === 0;
        if (noSelectionMessage) noSelectionMessage.hidden = picked.length > 0;
        if (selectAll) {
            selectAll.checked = picked.length === checkboxes.length;
            selectAll.indeterminate = picked.length > 0 && picked.length < checkboxes.length;
        }
    }

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        update();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
    update();
})();
