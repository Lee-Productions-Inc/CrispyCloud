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
// Step 46 — Live Product Image Preview
(() => {
    const imageInput = document.getElementById('p-image');
    const preview = document.querySelector('.ap-image-preview');

    if (!imageInput || !preview) return;

    const originalPreview = preview.innerHTML;
    let imageURL = null;

    const message = document.createElement('small');
    message.className = 'ap-invalid';
    message.setAttribute('role', 'status');
    imageInput.insertAdjacentElement('afterend', message);

    imageInput.addEventListener('change', () => {
        message.textContent = '';

        if (imageURL) {
            URL.revokeObjectURL(imageURL);
            imageURL = null;
        }

        const file = imageInput.files?.[0];

        if (!file) {
            preview.innerHTML = originalPreview;
            return;
        }

        // Accept PNG, JPG, and JPEG only.
        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            message.textContent = 'Please select a PNG, JPG, or JPEG image.';
            imageInput.value = '';
            preview.innerHTML = originalPreview;
            return;
        }

        // Maximum image size: 4 MB.
        if (file.size > 4 * 1024 * 1024) {
            message.textContent = 'Image must not exceed 4 MB.';
            imageInput.value = '';
            preview.innerHTML = originalPreview;
            return;
        }

        // Display the selected image immediately.
        imageURL = URL.createObjectURL(file);

        const image = document.createElement('img');
        image.src = imageURL;
        image.alt = 'Selected product image preview';

        const filename = document.createElement('small');
        filename.textContent = file.name;

        preview.replaceChildren(image, filename);
    });

    window.addEventListener('pagehide', () => {
        if (imageURL) URL.revokeObjectURL(imageURL);
    });
})();