(() => {
    'use strict';
    const choices = {
        add: ['Supplier purchase', 'Returned to stock', 'Inventory correction', 'Transfer in', 'Other'],
        remove: ['Spoiled or expired', 'Damaged', 'Waste or spillage', 'Inventory correction', 'Internal use', 'Other']
    };
    document.querySelectorAll('.ai-adjust-form').forEach(form => {
        const direction = form.querySelector('select[name="direction"]');
        const reason = form.querySelector('select[name="reason"]');
        const otherWrap = form.querySelector('[data-stock-other-wrap]');
        const other = form.querySelector('input[name="reason_other"]');
        if (!direction || !reason || !otherWrap || !other) return;
        function updateOther() {
            const enabled = reason.value === 'Other';
            otherWrap.hidden = !enabled;
            other.disabled = !enabled;
            other.required = enabled;
            if (!enabled) other.value = '';
        }
        function updateReasons() {
            const selected = reason.value;
            reason.replaceChildren(new Option('Select reason', ''));
            (choices[direction.value] || []).forEach(value => reason.add(new Option(value, value)));
            if ((choices[direction.value] || []).includes(selected)) reason.value = selected;
            updateOther();
        }
        direction.addEventListener('change', updateReasons);
        reason.addEventListener('change', updateOther);
        updateReasons();
    });
})();
