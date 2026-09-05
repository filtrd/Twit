export function initProfile() {
    document.addEventListener('click', event => {
        const button = event.target.closest('.profile-detail-edit');
        if (!button) return;

        const detail = button.closest('.profile-detail');
        if (!detail || detail.querySelector('.profile-detail-form')) return;

        const field = detail.dataset.profileDetail;
        const value = detail.dataset.value || '';
        const valueElement = detail.querySelector('.profile-detail-value');
        if (!valueElement) return;

        const form = document.createElement('form');
        form.className = 'profile-detail-form';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = value;
        input.maxLength = field === 'website' ? 253 : 100;
        input.autocomplete = 'off';
        input.spellcheck = field !== 'website';
        if (field === 'website') input.placeholder = 'example.com';

        const actions = document.createElement('span');
        actions.className = 'profile-detail-actions';

        const save = document.createElement('button');
        save.type = 'submit';
        save.textContent = 'Save';

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.textContent = 'Cancel';

        actions.append(save, cancel);
        form.append(input, actions);
        valueElement.replaceWith(form);
        button.hidden = true;
        input.focus();
        input.select();

        cancel.addEventListener('click', () => {
            form.replaceWith(valueElement);
            button.hidden = false;
        });

        form.addEventListener('submit', async submitEvent => {
            submitEvent.preventDefault();
            save.disabled = true;

            const csrf = document.querySelector('input[name="csrf"]')?.value || '';
            const body = new URLSearchParams({
                action: 'update-profile-detail',
                field,
                value: input.value,
                csrf
            });

            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Could not save.');

                const cleanedValue = data.value || '';
                detail.dataset.value = cleanedValue;

                if (field === 'website' && cleanedValue) {
                    const link = document.createElement('a');
                    link.className = 'profile-detail-value';
                    link.href = 'https://' + cleanedValue;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = cleanedValue;
                    form.replaceWith(link);
                } else {
                    const span = document.createElement('span');
                    span.className = 'profile-detail-value';
                    span.textContent = cleanedValue || (field === 'website' ? 'Add website' : 'Add location');
                    form.replaceWith(span);
                }
                button.hidden = false;
            } catch (error) {
                save.disabled = false;
                let message = detail.querySelector('.profile-detail-message');
                if (!message) {
                    message = document.createElement('span');
                    message.className = 'profile-detail-message';
                    detail.append(message);
                }
                message.textContent = error.message;
            }
        });
    });
}
