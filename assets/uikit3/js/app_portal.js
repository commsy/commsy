// Globals first: the legacy inline scripts read window.jQuery, and ES module
// imports are evaluated in order, so this must stay on top.
import '../../globals.js';

// CSS
import '../css/commsy.less';
import 'nprogress/nprogress.css';
import 'flatpickr/dist/themes/light.css';

// JS
import 'expose-loader?exposes=NProgress!nprogress/nprogress.js';
import 'moment/moment.js';
import 'expose-loader?exposes=URI!urijs/src/URI.js';

import UIkit from 'uikit3';
import Icons from 'uikit3/dist/js/uikit-icons.js';

// loads the Icon plugin
UIkit.use(Icons);

// import {Edit} from "./commsy/Edit.ts";
// Edit.bootstrap();

import {Upload} from "./commsy/Upload.ts";
Upload.bootstrap();

import {DatePicker} from "./commsy/DatePicker.ts";
DatePicker.bootstrap();

import {LicenseEdit} from "./commsy/LicenseEdit.ts";
LicenseEdit.bootstrap();

import {FormCollection} from "./commsy/FormCollection.ts";
FormCollection.bootstrap();

import {handleShibIdPSelect} from "./commsy/Login.ts";
handleShibIdPSelect();

// start the Stimulus application
import '../../bootstrap.js';
