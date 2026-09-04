import { initCommon } from './common.js';
import { initFeed } from './feed.js';
import { initComposer } from './composer.js';

const common = initCommon();
initFeed(common);
initComposer();
