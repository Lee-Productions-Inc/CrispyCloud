// GCash reference is required only when the customer selects GCash.
const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
const referenceField = document.getElementById('cc-gcash-reference');
const referenceInput = document.getElementById('gcash-reference');

if (paymentInputs.length && referenceField && referenceInput) {
    const syncPaymentFields = () => {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        const isGCash = selected?.value === 'GCash';
        referenceField.hidden = !isGCash;
        referenceInput.disabled = !isGCash;
        referenceInput.required = isGCash;
    };

    referenceInput.addEventListener('input', () => {
        referenceInput.value = referenceInput.value.replace(/[^0-9]/g, '').slice(0, 12);
    });
    paymentInputs.forEach((input) => input.addEventListener('change', syncPaymentFields));
    syncPaymentFields();
}
