(() => {
    'use strict';
    const $ = (id) => document.getElementById(id);
    const dataElement = $('pos-catalog-json');
    const form = $('pos-sale-form');
    if (!dataElement || !form) return;

    let catalog = [];
    try { catalog = JSON.parse(dataElement.textContent); } catch (_) { catalog = []; }
    if (!Array.isArray(catalog)) catalog = [];
    const byId = new Map(catalog.map((product) => [Number(product.id), product]));
    const pick = $('pos-product');
    const search = $('pos-product-search');
    const grid = $('pos-product-grid');
    if (!pick || !grid) return; // No products yet: display the server-rendered empty state.
    const addonsBox = $('pos-addons');
    const removalsBox = $('pos-removals');
    const foodOptions = $('pos-food-options');
    const beverageNote = $('pos-beverage-note');
    const sauceWrap = $('pos-sauce-wrap');
    const sauceSelect = $('pos-sauce');
    const qty = $('pos-qty');
    const notes = $('pos-notes');
    const itemsInput = $('pos-items-json');
    const cart = $('pos-cart-lines');
    const total = $('pos-grand-total');
    const cashInput = $('pos-cash-received');
    const change = $('pos-change');
    const cashBlock = $('pos-cash-block');
    const gcashBlock = $('pos-gcash-block');
    const gcashRef = $('pos-gcash-ref');
    const errorBox = $('pos-client-error');
    const paymentCheckbox = form.querySelector('input[name="payment_confirmed"]');
    const kindButtons = [...document.querySelectorAll('[data-pos-kind]')];
    const money = (cents) => '₱' + (cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const method = () => form.querySelector('input[name="payment_method"]:checked')?.value || 'Cash';
    let lines = [];
    let kind = 'food';
    let category = 'all';
    const categoryFilters = $('pos-category-filters');

    function make(tag, cls, content) {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (content !== undefined) node.textContent = content;
        return node;
    }
    function showError(message) {
        errorBox.hidden = !message;
        errorBox.textContent = message || '';
        const pickerError = $('pos-picker-error');
        if (pickerError) { pickerError.hidden = !message; pickerError.textContent = message || ''; }
    }
    function resetConfirmation() {
        if (paymentCheckbox) paymentCheckbox.checked = false;
    }
    function chosenProduct() { return byId.get(Number(pick.value)); }
    function checkedValues(fieldset) {
        return [...fieldset.querySelectorAll('input:checked')].map((input) => Number(input.value));
    }
    function clearOptions(fieldset, legend, values, prefix, onChange) {
        fieldset.replaceChildren(make('legend', '', legend));
        if (!values.length) {
            fieldset.append(make('p', 'pos-help', 'No options for this product.'));
            return;
        }
        values.forEach((value) => {
            const label = make('label', 'pos-check');
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = prefix;
            input.value = value.id;
            input.addEventListener('change', onChange);
            const name = value.price_cents === undefined ? value.name : value.name + ' · +' + money(value.price_cents);
            label.append(input, make('span', '', name));
            fieldset.append(label);
        });
    }
    function selectedRemovals() { return checkedValues(removalsBox); }
    function updateSauce() {
        const product = chosenProduct();
        const hasSauce = product?.kind === 'food' && product.components.some((component) => component.name.toLowerCase().includes('sauce'));
        const withoutSauce = product?.components.some((component) => component.name.toLowerCase().includes('sauce') && selectedRemovals().includes(component.id));
        sauceWrap.hidden = !hasSauce || !!withoutSauce;
        sauceSelect.disabled = sauceWrap.hidden;
    }
    function linePrice(item) {
        const product = byId.get(item.product_id);
        if (!product) return 0;
        return product.price_cents + item.add_ons.reduce((sum, id) =>
            sum + (product.addons.find((addon) => addon.id === id)?.price_cents || 0), 0);
    }
    function currentPreviewPrice() {
        const product = chosenProduct();
        if (!product) return 0;
        const extras = product.kind === 'food' ? checkedValues(addonsBox) : [];
        return (product.price_cents + extras.reduce((sum, id) =>
            sum + (product.addons.find((addon) => addon.id === id)?.price_cents || 0), 0)) * Math.max(1, Math.min(20, Number(qty.value) || 1));
    }
    function previewPrice() {
        $('pos-item-preview-total').textContent = money(currentPreviewPrice());
    }
    function showProduct() {
        const product = chosenProduct();
        $('pos-configuration').hidden = !product;
        if (!product) return;
        $('pos-selected-title').textContent = product.name;
        $('pos-selected-product').textContent = product.category + ' · ' + money(product.price_cents) + ' per item';
        const drink = product.kind === 'beverage';
        foodOptions.hidden = drink;
        beverageNote.hidden = !drink;
        clearOptions(addonsBox, 'Food extras (not drinks)', drink ? [] : product.addons, 'pos-local-addon', previewPrice);
        clearOptions(removalsBox, 'Remove ingredients', drink ? [] : product.components, 'pos-local-removal', updateSauce);
        updateSauce();
        previewPrice();
    }
    function renderCategoryFilters() {
        if (!categoryFilters) return;
        categoryFilters.replaceChildren();
        const categories = [...new Set(catalog.filter((p) => p.kind === kind).map((p) => p.category))]
            .sort((a, b) => a.localeCompare(b));
        const entries = ['all', ...categories];
        entries.forEach((value) => {
            const button = make('button', 'pos-category-button', value === 'all' ? 'All ' + (kind === 'beverage' ? 'beverages' : 'food') : value);
            button.type = 'button';
            button.classList.toggle('is-active', category === value);
            button.setAttribute('aria-pressed', String(category === value));
            button.addEventListener('click', () => {
                category = value;
                pick.value = '';
                showProduct();
                renderCategoryFilters();
                renderCatalog();
            });
            categoryFilters.append(button);
        });
    }
    function switchKind(next) {
        kind = next;
        category = 'all';
        pick.value = '';
        search.value = '';
        showProduct();
        kindButtons.forEach((tab) => {
            const active = tab.dataset.posKind === next;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-pressed', String(active));
        });
        renderCategoryFilters();
        renderCatalog();
        search.focus();
    }
    function renderCatalog() {
        if (!grid) return;
        grid.replaceChildren();
        const searchText = (search?.value || '').trim().toLocaleLowerCase();
        const matching = catalog.filter((product) => product.kind === kind &&
            (category === 'all' || product.category === category) &&
            (product.name.toLocaleLowerCase().includes(searchText) || product.category.toLocaleLowerCase().includes(searchText)));
        $('pos-catalog-count').textContent = matching.length + (matching.length === 1 ? ' item' : ' items') + ' in ' + (kind === 'food' ? 'Food' : 'Beverages');
        if (!matching.length) {
            const empty = make('div', 'pos-catalog-empty');
            empty.append(make('strong', '', kind === 'beverage' ? 'No beverages found' : 'No matching food items'));
            empty.append(make('p', 'pos-help', kind === 'beverage'
                ? 'Create a product in the Drinks or Beverages category. Drinks are not food extras.'
                : 'Try another search or check product availability.'));
            grid.append(empty);
            return;
        }
        matching.forEach((product) => {
            const button = make('button', 'pos-product-card');
            button.type = 'button';
            button.setAttribute('aria-pressed', String(Number(pick.value) === product.id));
            button.setAttribute('aria-label', 'Select ' + product.name + ', ' + money(product.price_cents));
            if (Number(pick.value) === product.id) button.classList.add('is-selected');
            const imageHolder = make('span', 'pos-product-art');
            if (product.image_url) {
                const image = document.createElement('img');
                image.src = product.image_url;
                image.alt = '';
                image.loading = 'lazy';
                imageHolder.append(image);
            } else {
                imageHolder.append(make('span', '', kind === 'beverage' ? '🥤' : '🍗'));
            }
            const description = make('span', 'pos-product-details');
            description.append(make('strong', '', product.name));
            description.append(make('small', '', product.category));
            description.append(make('b', '', money(product.price_cents)));
            button.append(imageHolder, description);
            button.addEventListener('click', () => {
                pick.value = String(product.id);
                qty.value = '1';
                notes.value = '';
                showError('');
                showProduct();
                renderCatalog();
                qty.focus();
            });
            grid.append(button);
        });
    }
    function normalizeItem(raw) {
        const product = byId.get(Number(raw?.product_id));
        const quantity = Number(raw?.quantity);
        if (!product || !Number.isInteger(quantity) || quantity < 1 || quantity > 20) return null;
        const addonIds = Array.isArray(raw.add_ons) ? raw.add_ons.map(Number) : [];
        const removalIds = Array.isArray(raw.remove_components) ? raw.remove_components.map(Number) : [];
        if (addonIds.some((id) => !product.addons.some((addon) => addon.id === id)) ||
            removalIds.some((id) => !product.components.some((component) => component.id === id))) return null;
        if (new Set(addonIds).size !== addonIds.length || new Set(removalIds).size !== removalIds.length) return null;
        if (product.kind === 'beverage' && (addonIds.length || removalIds.length || raw.sauce_choice)) return null;
        return {
            product_id: product.id, quantity,
            add_ons: addonIds, remove_components: removalIds,
            sauce_choice: raw.sauce_choice || null,
            item_notes: String(raw.item_notes || '').slice(0, 500),
        };
    }
    function basketTotal() {
        return lines.reduce((sum, item) => sum + linePrice(item) * item.quantity, 0);
    }
    function renderBasket() {
        cart.replaceChildren();
        if (!lines.length) {
            cart.append(make('p', 'pos-empty', 'Your basket is empty. Select Food or Beverages to start.'));
        }
        lines.forEach((item, index) => {
            const product = byId.get(item.product_id);
            const row = make('article', 'pos-cart-row');
            const detail = make('div', 'pos-cart-item-content');
            const category = make('small', '', product.kind === 'beverage' ? 'Beverage' : 'Food');
            detail.append(category, make('strong', '', product.name));
            const extras = product.addons.filter((a) => item.add_ons.includes(a.id));
            const removes = product.components.filter((c) => item.remove_components.includes(c.id));
            if (extras.length) detail.append(make('small', '', 'Extras: ' + extras.map((a) => a.name).join(', ')));
            if (removes.length) detail.append(make('small', '', 'Without: ' + removes.map((c) => c.name).join(', ')));
            if (item.sauce_choice) detail.append(make('small', '', 'Sauce: ' + item.sauce_choice));
            if (item.item_notes) detail.append(make('small', '', 'Notes: ' + item.item_notes));

            const qtyTools = make('div', 'pos-qty-tools');
            const minus = make('button', '', '−');
            minus.type = 'button';
            minus.disabled = item.quantity <= 1;
            minus.setAttribute('aria-label', 'Decrease quantity of ' + product.name);
            minus.addEventListener('click', () => { item.quantity -= 1; resetConfirmation(); renderBasket(); });
            const qtyNumber = make('span', '', String(item.quantity));
            qtyNumber.setAttribute('aria-label', 'Quantity ' + item.quantity);
            const plus = make('button', '', '+');
            plus.type = 'button';
            plus.disabled = item.quantity >= 20;
            plus.setAttribute('aria-label', 'Increase quantity of ' + product.name);
            plus.addEventListener('click', () => { item.quantity += 1; resetConfirmation(); renderBasket(); });
            qtyTools.append(minus, qtyNumber, plus);
            const actions = make('div', 'pos-cart-item-actions');
            actions.append(make('strong', '', money(linePrice(item) * item.quantity)), qtyTools);
            const remove = make('button', 'pos-remove', 'Remove');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Remove ' + product.name);
            remove.addEventListener('click', () => { lines.splice(index, 1); resetConfirmation(); renderBasket(); });
            actions.append(remove);
            row.append(detail, actions);
            cart.append(row);
        });
        if (lines.some((item) => byId.get(item.product_id)?.kind === 'food') &&
            catalog.some((product) => product.kind === 'beverage')) {
            const suggestion = make('button', 'pos-browse-drinks', '🥤 Add a beverage to this sale');
            suggestion.type = 'button';
            suggestion.addEventListener('click', () => switchKind('beverage'));
            cart.append(suggestion);
        }
        total.textContent = money(basketTotal());
        itemsInput.value = JSON.stringify(lines);
        updateChange();
    }
    function updateChange() {
        const received = Math.round(Number(cashInput.value || 0) * 100);
        const remaining = received - basketTotal();
        const label = $('pos-change-label');
        if (label) label.textContent = remaining < 0 ? 'Still needed' : 'Change due';
        change.textContent = money(Math.abs(remaining));
        change.classList.toggle('pos-insufficient', received < basketTotal());
    }
    function updatePayment() {
        const cash = method() === 'Cash';
        cashBlock.hidden = !cash;
        gcashBlock.hidden = cash;
        cashInput.disabled = !cash;
        gcashRef.disabled = cash;
        cashInput.required = cash;
        gcashRef.required = !cash;
        updateChange();
    }

    try {
        const oldItems = JSON.parse(itemsInput.value);
        if (Array.isArray(oldItems)) lines = oldItems.map(normalizeItem).filter(Boolean).slice(0, 25);
    } catch (_) { lines = []; }

    kindButtons.forEach((button) => button.addEventListener('click', () => switchKind(button.dataset.posKind)));
    search?.addEventListener('input', renderCatalog);
    $('pos-clear-selection')?.addEventListener('click', () => {
        pick.value = '';
        showProduct();
        renderCatalog();
        search.focus();
    });
    qty?.addEventListener('input', previewPrice);
    $('pos-add-line')?.addEventListener('click', () => {
        showError('');
        const product = chosenProduct();
        const quantity = Number(qty.value);
        if (!product) return showError('Choose a menu item first.');
        if (!Number.isInteger(quantity) || quantity < 1 || quantity > 20) return showError('Quantity must be 1 to 20.');
        if (lines.length >= 25) return showError('A walk-in sale supports up to 25 item lines.');
        const item = normalizeItem({
            product_id: product.id, quantity,
            add_ons: product.kind === 'beverage' ? [] : checkedValues(addonsBox),
            remove_components: product.kind === 'beverage' ? [] : selectedRemovals(),
            sauce_choice: sauceWrap.hidden ? null : sauceSelect.value,
            item_notes: notes.value,
        });
        if (!item) return showError('Please review the selected options.');
        lines.push(item);
        resetConfirmation();
        renderBasket();
        qty.value = '1';
        notes.value = '';
        showProduct();
        showError('');
    });
    form.querySelectorAll('input[name="payment_method"]').forEach((input) => input.addEventListener('change', updatePayment));
    cashInput.addEventListener('input', updateChange);
    form.addEventListener('submit', (event) => {
        showError('');
        if (!lines.length) { event.preventDefault(); return showError('Add at least one food or beverage item.'); }
        if (method() === 'Cash') {
            if (cashInput.value === '' || !Number.isFinite(Number(cashInput.value)) || Math.round(Number(cashInput.value) * 100) < basketTotal()) {
                event.preventDefault(); return showError('Enter cash received equal to or greater than the total.');
            }
        } else if (gcashRef.value.trim().length < 4) {
            event.preventDefault(); return showError('Enter the verified GCash transaction reference.');
        }
        if (!paymentCheckbox?.checked) {
            event.preventDefault(); return showError('Confirm that payment was actually received.');
        }
        itemsInput.value = JSON.stringify(lines);
        $('pos-record').disabled = true;
        $('pos-record').textContent = 'Recording sale…';
    });
    renderCategoryFilters();
    renderCatalog();
    showProduct();
    renderBasket();
    updatePayment();
})();
