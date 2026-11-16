document.addEventListener('DOMContentLoaded', () => {
  const control = document.getElementById('a-toggle-password');
  const field = document.getElementById('admin-password');
  if (!control || !field) return;

  control.addEventListener('click', () => {
    const isShowing = field.type === 'text';
    field.type = isShowing ? 'password' : 'text';
    control.textContent = isShowing ? 'Show' : 'Hide';
    control.setAttribute('aria-label', isShowing ? 'Show password' : 'Hide password');
    control.setAttribute('aria-pressed', String(!isShowing));
  });
});
