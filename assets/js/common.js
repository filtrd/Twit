function bindPostMenus() {
    document.querySelectorAll('.post-menu:not([data-menu-bound])').forEach(menu => {
        menu.dataset.menuBound = '1';
        const button = menu.querySelector('.post-menu-button');
        const dropdown = menu.querySelector('.post-menu-dropdown');
        if (!button || !dropdown) return;

        button.addEventListener('click', event => {
            event.stopPropagation();
            const open = !dropdown.hidden;
            document.querySelectorAll('.post-menu-dropdown').forEach(item => item.hidden = true);
            document.querySelectorAll('.post-menu-button').forEach(item => item.setAttribute('aria-expanded', 'false'));
            dropdown.hidden = open;
            button.setAttribute('aria-expanded', String(!open));
        });
    });
}

export function initCommon() {
    const deleteDialog = document.getElementById('delete-dialog');
    let pendingDeleteForm = null;

    if (deleteDialog) {
        document.querySelectorAll('.post-delete-form:not([data-delete-bound])').forEach(form => {
            form.dataset.deleteBound = '1';
            form.addEventListener('submit', event => {
                event.preventDefault();
                pendingDeleteForm = form;
                deleteDialog.showModal();
            });
        });

        deleteDialog.addEventListener('close', () => {
            if (deleteDialog.returnValue === 'confirm' && pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
            pendingDeleteForm = null;
        });
    }

    bindPostMenus();

    document.addEventListener('click', () => {
        document.querySelectorAll('.post-menu-dropdown').forEach(item => item.hidden = true);
        document.querySelectorAll('.post-menu-button').forEach(item => item.setAttribute('aria-expanded', 'false'));
    });

    return {
        bindLoadedPostActions() {
            if (deleteDialog) {
                document.querySelectorAll('.post-delete-form:not([data-delete-bound])').forEach(form => {
                    form.dataset.deleteBound = '1';
                    form.addEventListener('submit', event => {
                        event.preventDefault();
                        pendingDeleteForm = form;
                        deleteDialog.showModal();
                    });
                });
            }
            bindPostMenus();
        }
    };
}
