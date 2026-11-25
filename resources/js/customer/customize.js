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
    const sauceOptions = [...customizer.querySelectorAll('#cc-sauce-options input[type="checkbox"]')];
    const sauceLimit = Number(customizer.querySelector('[data-sauce-limit]')?.dataset.sauceLimit || 0);
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

    const refreshSauceState = () => {
        const removeSauce = sauceRemovals.some((checkbox) => checkbox.checked);
        if (removeSauce) {
            sauceOptions.forEach((option) => { option.checked = false; option.disabled = true; });
        } else {
            sauceOptions.forEach((option) => { option.disabled = false; });
        }
        if (sauceDescription) {
            const chosen = sauceOptions.filter((option) => option.checked).map((option) => option.value);
            sauceDescription.textContent = removeSauce ? 'No sauce' :
                (sauceDescription.dataset.fixedSauce || (chosen.length ? chosen.join(', ') : 'Choose your sauce'));
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
    const updateAddonControls = () => {
        customizer.querySelectorAll('[data-addon-step]').forEach((button) => {
            const input = document.getElementById(button.dataset.addonTarget);
            if (!input) return;
            const count = Math.max(0, Math.min(10, Number.parseInt(input.value, 10) || 0));
            button.disabled = Number(button.dataset.addonStep) < 0 ? count <= 0 : count >= 10;
        });
    };

    customizer.querySelectorAll('[data-addon-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.addonTarget);
            if (!input) return;
            const count = Math.max(0, Math.min(10, Number.parseInt(input.value, 10) || 0));
            input.value = String(Math.max(0, Math.min(10, count + Number(button.dataset.addonStep))));
            updateTotal();
            updateAddonControls();
        });
    });

    customizer.querySelectorAll('input[data-addon-price]').forEach((input) => {
        input.addEventListener('input', updateTotal);
        input.addEventListener('change', updateTotal);
    });
    sauceRemovals.forEach((checkbox) => checkbox.addEventListener('change', updateTotal));
    drinkOptions.forEach((radio) => radio.addEventListener('change', updateTotal));
    sauceOptions.forEach((option) => option.addEventListener('change', () => {
        if (option.checked && sauceOptions.filter((entry) => entry.checked).length > sauceLimit) {
            option.checked = false;
        }
        refreshSauceState();
    }));

    updateTotal();
    updateAddonControls();
}
