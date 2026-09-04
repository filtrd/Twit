import { initCommon } from './common.js';
import { initFeed } from './feed.js';
import { initComposer } from './composer.js';

initCommon();
initFeed();

if (document.querySelector('.composer')) {
    initComposer();
}

const avatarUpload = document.getElementById('avatar-upload');
if (avatarUpload) {
    avatarUpload.addEventListener('change', () => {
        if (avatarUpload.files.length) avatarUpload.closest('form')?.submit();
    });
}
