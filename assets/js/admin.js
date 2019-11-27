'use strict';

import '../sass/admin.scss';
import Stats from './components/Stats.js';

(function() {
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-elasticsearch',
        'Stats',
        new Stats()
    );
})();
