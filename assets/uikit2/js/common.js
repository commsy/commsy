// CSS
require('jstree/dist/themes/default/style.css');
require('nprogress/nprogress.css');
require('fullcalendar/dist/fullcalendar.css');
require('tooltipster/dist/css/tooltipster.bundle.css');
require('video.js/dist/video-js.css');
require('select2/dist/css/select2.css');

// JS
import "@babel/polyfill";

// load third party packages
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

require('expose-loader?UIkit!uikit2/dist/js/uikit');
require('uikit2/dist/js/components/autocomplete');
require('uikit2/dist/js/components/search');
require('uikit2/dist/js/components/nestable');
require('uikit2/dist/js/components/tooltip');
require('uikit2/dist/js/components/grid');
require('uikit2/dist/js/components/accordion');
require('uikit2/dist/js/components/upload');
require('uikit2/dist/js/components/sticky');
require('uikit2/dist/js/components/slider');
require('uikit2/dist/js/components/lightbox');
require('uikit2/dist/js/components/sortable');
require('uikit2/dist/js/components/notify');
require('uikit2/dist/js/components/parallax');
require('uikit2/dist/js/components/datepicker');
require('uikit2/dist/js/components/timepicker');
require('uikit2/dist/js/components/form-select');

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