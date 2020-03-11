// CSS
require('../css/commsy.less');

require('nprogress/nprogress.css');
require('select2/dist/css/select2.css');
require("flatpickr/dist/themes/light.css");

// JS
import "@babel/polyfill";

// load third party packages
require('expose-loader?$!jquery');

require('expose-loader?NProgress!nprogress/nprogress');
require('moment/moment');
require('expose-loader?URI!urijs/src/URI');
require('select2/dist/js/select2');

import UIkit from 'uikit';
import Icons from 'uikit/dist/js/uikit-icons';

// loads the Icon plugin
UIkit.use(Icons);

import {Edit} from "./commsy/Edit";
Edit.bootstrap();

import {Upload} from "./commsy/Upload";
Upload.bootstrap();

import {DatePicker} from "./commsy/DatePicker";
DatePicker.bootstrap();