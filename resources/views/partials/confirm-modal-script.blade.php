<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('confirmActionModal');

        if (!modalElement) {
            return;
        }

        modalElement.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const confirmButton = modalElement.querySelector('[data-confirm-submit]');
            const message = modalElement.querySelector('[data-confirm-message]');
            const formId = trigger?.getAttribute('data-confirm-form');

            message.textContent = trigger?.getAttribute('data-confirm-message') ?? 'Please confirm that you want to continue.';
            confirmButton.onclick = function () {
                if (formId) {
                    document.getElementById(formId)?.submit();
                }
            };
        });
    });
</script>
