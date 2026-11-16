// Show optional reference number for manual GCash verification.
const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
const referenceField = document.getElementById('cc-gcash-reference');

if (paymentInputs.length && referenceField) {
    const syncPaymentFields = () => {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        referenceField.hidden = selected?.value !== 'GCash';
    };

    paymentInputs.forEach((input) => input.addEventListener('change', syncPaymentFields));
    syncPaymentFields();
}
