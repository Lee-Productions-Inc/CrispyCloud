/** Accessible Show/Hide password buttons on customer login and registration. */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;

    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
    const isConfirm = input.name === 'password_confirmation';
    button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${isConfirm ? 'confirm password' : 'password'}`);
    const label = button.querySelector('[data-password-label]');
    if (label) label.textContent = show ? 'Hide' : 'Show';
});
