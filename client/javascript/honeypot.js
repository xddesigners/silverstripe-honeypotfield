document.addEventListener('DOMContentLoaded', function () {
    // Hide the trap field holder via CSS class (no inline style)
    var holder = document.querySelector('.contact-fields-group');
    if (holder) {
        holder.classList.add('extra-contact-fields');
    }

    // Set the interaction token on real user activity.
    // Field name matches HoneypotField::$interaction_field ('page_token').
    var tokenField = document.querySelector('input[name="page_token"]');
    if (tokenField) {
        var activate = function () { tokenField.value = '1'; };
        document.addEventListener('mousemove',  activate, { once: true });
        document.addEventListener('keydown',    activate, { once: true });
        document.addEventListener('touchstart', activate, { once: true });
    }
});
