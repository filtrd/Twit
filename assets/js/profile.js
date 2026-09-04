import { initCommon } from './common.js';
import { initFeed } from './feed.js';

const common = initCommon();

const avatarUpload = document.getElementById('avatar-upload');
if (avatarUpload) {
    avatarUpload.addEventListener('change', () => {
        if (avatarUpload.files.length) avatarUpload.closest('form')?.submit();
    });
}

initFeed(common);
