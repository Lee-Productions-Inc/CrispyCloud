// Shared, keyboard-accessible product photo preview (menu and customization).
const dialog = document.getElementById('cc-product-preview');
if (dialog && typeof dialog.showModal === 'function') {
    const image = dialog.querySelector('[data-preview-image]');
    const title = dialog.querySelector('#cc-preview-title');
    const close = dialog.querySelector('[data-preview-close]');
    let trigger = null;

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-product-preview]');
        if (!button || !image || !title) return;
        trigger = button;
        title.textContent = button.dataset.previewName || 'Product photo';
        image.alt = button.dataset.previewName || 'Product photo';
        image.src = button.dataset.previewSrc;
        dialog.showModal();
        close?.focus();
    });

    close?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        image.removeAttribute('src');
        trigger?.focus();
        trigger = null;
    });
}
