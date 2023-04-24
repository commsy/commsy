// CSS
require('jstree/dist/themes/default/style.css');
require('nprogress/nprogress.css');
require('tooltipster/dist/css/tooltipster.bundle.css');
require('video.js/dist/video-js.css');

// JS
const $ = require('jquery');
global.$ = global.jQuery = $;

require('jstree/dist/jstree');
require('expose-loader?exposes=NProgress!nprogress/nprogress');
require('moment/moment');
require('tooltipster/dist/js/tooltipster.bundle');
require('expose-loader?exposes=URI!urijs/src/URI');
require('video.js/dist/video');
require('jscolor-picker/jscolor');

require(['uikit'], function () {
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

import '@fullcalendar/core/main.css';
import '@fullcalendar/daygrid/main.css';
import '@fullcalendar/timegrid/main.css';
import deLocale from '@fullcalendar/core/locales/de';
import enLocale from '@fullcalendar/core/locales/en-gb';
import { setup as setupCalendar } from "./commsy/fullcalendar";
setupCalendar([deLocale, enLocale], 'calendar');
setupCalendar([deLocale, enLocale], 'calendarDashboard', false);

// start the Stimulus application
import '../../bootstrap';
