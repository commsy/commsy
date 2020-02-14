// CSS
require('jstree/dist/themes/default/style.css');
require('nprogress/nprogress.css');
require('fullcalendar/dist/fullcalendar.css');
require('tooltipster/dist/css/tooltipster.bundle.css');
require('video.js/dist/video-js.css');
require('select2/dist/css/select2.css');
require("flatpickr/dist/themes/light.css");

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

import UIkit from 'uikit';
import Icons from 'uikit/dist/js/uikit-icons';

// loads the Icon plugin
UIkit.use(Icons);

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

import {Edit} from "./commsy/Edit";
Edit.bootstrap();

import {DatePicker} from "./commsy/DatePicker";
DatePicker.bootstrap();