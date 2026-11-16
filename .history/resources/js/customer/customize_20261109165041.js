// Product customization previews are client-side only. CartController/CheckoutController
// validate selected IDs and recalculate all prices on the Laravel server.
const customizer = document.querySelector('[data-customizer]');

if (customizer) {
    const quantityInput = customizer.querySelector('#item-quantity');
    const totalDisplay = customizer.querySelector('#item-total');
    const buttonTotal = customizer.querySelector('#cart-button-total');
    const unitDisplay = customizer.querySelector('#ccx-unit-price');
    const extrasDisplay = customizer.querySelector('#ccx-extras-price');
    const extrasDescription = customizer.querySelector('#ccx-extras-description');
    const sauceDescription = customizer.querySelector('#ccx-sauce-selected');
    const decreaseButton = customizer.querySelector('[data-quantity-step="-1"]');
    const increaseButton = customizer.querySelector('[data-quantity-step="1"]');
    const sauceRemovals = [...customizer.querySelectorAll('[data-remove-sauce]')];
    const sauceOptions = [...customizer.querySelectorAll('#cc-sauce-options input[type="radio"]')];
    const formatPrice = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const basePrice = Number(customizer.dataset.basePrice) || 0;
    const drinkPriceDisplay = customizer.querySelector('#ccx-drink-price');
    const drinkSelectedDisplay = customizer.querySelector('#ccx-drink-selected');
    const drinkOptions = [...customizer.querySelectorAll('input[name="beverage_id"]')];

    const normalizeQuantity = () => {
        const parsed = Number.parseInt(quantityInput.value, 10);
        const quantity = Number.isFinite(parsed) ? Math.max(1, Math.min(20, parsed)) : 1;
        quantityInput.value = String(quantity);
        return quantity;
    };

    let previousSauce = sauceOptions.find((radio) => radio.checked)?.value || sauceOptions[0]?.value;

    const refreshSauceState = () => {
        const removeSauce = sauceRemovals.some((checkbox) => checkbox.checked);
        if (removeSauce) {
            const picked = sauceOptions.find((radio) => radio.checked);
            if (picked) previousSauce = picked.value;
            sauceOptions.forEach((radio) => {
                radio.checked = false;
                radio.disabled = true;
            });
        } else {
            sauceOptions.forEach((radio) => { radio.disabled = false; });
            if (sauceOptions.length && !sauceOptions.some((radio) => radio.checked)) {
                const preferred = sauceOptions.find((radio) => radio.value === previousSauce) || sauceOptions[0];
                preferred.checked = true;
            }
        }
        if (sauceDescription) {
            sauceDescription.textContent = removeSauce
                ? 'No sauce'
                : (sauceOptions.find((radio) => radio.checked)?.value || 'Not selected');
        }
    };

    const updateTotal = () => {
        const quantity = normalizeQuantity();
        customizer.querySelectorAll('input[data-addon-price]').forEach((input) => {
            const raw = Number.parseInt(input.value, 10);
            if (input.value !== '' && Number.isFinite(raw)) input.value = String(Math.max(0, Math.min(10, raw)));
        });
        const extras = [...customizer.querySelectorAll('input[data-addon-price]')]
            .map((input) => ({
                input,
                count: Math.max(0, Math.min(10, Number.parseInt(input.value, 10) || 0)),
            })).filter(({ count }) => count > 0);
        const extrasPrice = extras.reduce((sum, { input, count }) =>
            sum + (Number(input.dataset.addonPrice) || 0) * count, 0);
        const unitPrice = basePrice + extrasPrice;
        const chosenDrink = drinkOptions.find((input) => input.checked);
        const drinkPrice = Number(chosenDrink?.dataset.beveragePrice || 0);
        const total = (unitPrice + drinkPrice) * quantity;
        if (drinkPriceDisplay) drinkPriceDisplay.textContent = formatPrice.format(drinkPrice);
        if (drinkSelectedDisplay) drinkSelectedDisplay.textContent = chosenDrink?.value
            ? `With ${chosenDrink.dataset.beverageName} (separate cart item)` : 'No drink selected';

        if (extrasDisplay) extrasDisplay.textContent = formatPrice.format(extrasPrice);
        if (unitDisplay) unitDisplay.textContent = formatPrice.format(unitPrice);
        if (totalDisplay) totalDisplay.textContent = formatPrice.format(total);
        if (buttonTotal) buttonTotal.textContent = formatPrice.format(total);
        if (extrasDescription) {
            extrasDescription.textContent = extras.length
                ? extras.map(({ input, count }) => `${count} × ${input.dataset.addonName || 'Extra'} per meal`).join(', ')
                : 'No extras selected.';
        }
        if (decreaseButton) decreaseButton.disabled = quantity <= 1;
        if (increaseButton) increaseButton.disabled = quantity >= 20;
        refreshSauceState();
    };

    customizer.querySelectorAll('[data-quantity-step]').forEach((button) => {
        button.addEventListener('click', () => {
            quantityInput.value = String(Number(quantityInput.value || 1) + Number(button.dataset.quantityStep));
            updateTotal();
        });
    });
    quantityInput.addEventListener('input', updateTotal);
    quantityInput.addEventListener('change', updateTotal);
    customizer.querySelectorAll('input[data-addon-price]').forEach((input) => {
        input.addEventListener('input', updateTotal);
        input.addEventListener('change', updateTotal);
    });
    sauceRemovals.forEach((checkbox) => checkbox.addEventListener('change', updateTotal));
    drinkOptions.forEach((radio) => radio.addEventListener('change', updateTotal));
    sauceOptions.forEach((radio) => radio.addEventListener('change', () => {
        previousSauce = radio.value;
        refreshSauceState();
    }));

    updateTotal();
}
