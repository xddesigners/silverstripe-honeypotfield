document.addEventListener('DOMContentLoaded', function () {
    // Hide ALL trap field holders via CSS class (a page may contain multiple
    // forms, e.g. a userform plus the footer newsletter form).
    document.querySelectorAll('.contact-fields-group').forEach(function (holder) {
        holder.classList.add('extra-contact-fields');
    });

    // Set the interaction token on real user activity, for EVERY form on the page.
    // Field name matches HoneypotField::$interaction_field ('page_token').
    var tokenFields = document.querySelectorAll('input[name="page_token"]');
    if (tokenFields.length) {
        var activate = function () {
            tokenFields.forEach(function (tokenField) { tokenField.value = '1'; });
        };
        document.addEventListener('mousemove',  activate, { once: true });
        document.addEventListener('keydown',    activate, { once: true });
        document.addEventListener('touchstart', activate, { once: true });
    }
});
