// CSS
require('jstree/dist/themes/default/style.css');
require('nprogress/nprogress.css');
require('fullcalendar/dist/fullcalendar.css');
require('tooltipster/dist/css/tooltipster.bundle.css');
require('video.js/dist/video-js.css');
require('select2/dist/css/select2.css');

// JS
require('expose-loader?$!jquery');

require('jstree/dist/jstree');
require('expose-loader?NProgress!nprogress/nprogress');
require('moment/moment');
require('fullcalendar/dist/fullcalendar');
require('fullcalendar/dist/locale-all');
require('tooltipster/dist/js/tooltipster.bundle');
require('expose-loader?URI!urijs/src/URI');
require('video.js/dist/video');
require('jscolor-picker/jscolor');
require('select2/dist/js/select2');
require('select2/dist/js/i18n/de');
require('select2/dist/js/i18n/en');

require('expose-loader?UIkit!uikit/dist/js/uikit');
require('uikit/dist/js/components/autocomplete');
require('uikit/dist/js/components/search');
require('uikit/dist/js/components/nestable');
require('uikit/dist/js/components/tooltip');
require('uikit/dist/js/components/grid');
require('uikit/dist/js/components/accordion');
require('uikit/dist/js/components/upload');
require('uikit/dist/js/components/sticky');
require('uikit/dist/js/components/slider');
require('uikit/dist/js/components/lightbox');
require('uikit/dist/js/components/sortable');
require('uikit/dist/js/components/notify');
require('uikit/dist/js/components/parallax');
require('uikit/dist/js/components/datepicker');
require('uikit/dist/js/components/timepicker');
require('uikit/dist/js/components/form-select');

// import commsy modules
var commsyModules = require.context('./commsy', true, /\.js$/);
commsyModules.keys().forEach(function(key) {
    commsyModules(key);
});

import {DetailActionManager} from "./commsy/actions/DetailActionManager";
let detailActionManager = new DetailActionManager();
detailActionManager.registerActors();

import {ListActionManager} from "./commsy/actions/ListActionManager";
let listActionManager = new ListActionManager();
listActionManager.bootstrap();

import {Portfolio} from "./commsy/Portfolio";
let portfolio = new Portfolio();
portfolio.bootstrap();

import {MathJax} from "./commsy/MathJax";
let mathJax = new MathJax();
mathJax.bootstrap();