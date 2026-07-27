// Globals first: UIKit 2 and the legacy inline scripts read window.jQuery,
// and ES module imports are evaluated in order, so this must stay on top.
import '../../globals.js';

// CSS
import 'jstree/dist/themes/default/style.css';
import 'nprogress/nprogress.css';
import 'tooltipster/dist/css/tooltipster.bundle.css';
import 'video.js/dist/video-js.css';

// jQuery plugins and standalone libraries
import 'jstree/dist/jstree.js';
import 'expose-loader?exposes=NProgress!nprogress/nprogress.js';
import 'moment/moment.js';
import 'tooltipster/dist/js/tooltipster.bundle.js';
import 'expose-loader?exposes=URI!urijs/src/URI.js';
import 'video.js/dist/video.js';
import 'jscolor-picker/jscolor.js';

// UIKit 2 core, then its components (both expect window.jQuery/UIkit)
import 'uikit';
import 'uikit/dist/js/components/autocomplete.js';
import 'uikit/dist/js/components/search.js';
import 'uikit/dist/js/components/nestable.js';
import 'uikit/dist/js/components/tooltip.js';
import 'uikit/dist/js/components/grid.js';
import 'uikit/dist/js/components/accordion.js';
import 'uikit/dist/js/components/upload.js';
import 'uikit/dist/js/components/sticky.js';
import 'uikit/dist/js/components/slider.js';
import 'uikit/dist/js/components/lightbox.js';
import 'uikit/dist/js/components/sortable.js';
import 'uikit/dist/js/components/notify.js';
import 'uikit/dist/js/components/parallax.js';
import 'uikit/dist/js/components/datepicker.js';
import 'uikit/dist/js/components/timepicker.js';
import 'uikit/dist/js/components/form-select.js';

import {DetailActionManager} from "./commsy/actions/DetailActionManager.ts";
import {ListActionManager} from "./commsy/actions/ListActionManager.ts";
import {MathJax} from "./commsy/MathJax.ts";
import { setup as setupCalendar } from "./commsy/fullcalendar.ts";

// start the Stimulus application
import '../../bootstrap.js';

// import commsy modules
const commsyModules = import.meta.webpackContext('./commsy', {
    recursive: true,
    regExp: /\.js$/,
});
commsyModules.keys().forEach(function(key) {
    commsyModules(key);
});

let detailActionManager = new DetailActionManager();
detailActionManager.registerActors();

let listActionManager = new ListActionManager();
listActionManager.bootstrap();

let mathJax = new MathJax();
mathJax.bootstrap();

setupCalendar('calendar');
setupCalendar('calendarDashboard', false);
