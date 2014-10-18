<?php
// $Id: commsy_portal_css.php,v 1.4 2008/09/30 09:05:04 jackewitz Exp $
//
// Release $Name:  $
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

header("Content-type: text/css");
// load required classes
?>



/* $Id: style.css,v 1.14.2.4 2007/07/09 03:50:59 drumm Exp $ */

/*** Generic elements */


* {
  margin: 0;
  padding: 0;
}

html {
  height: 100%;
}

.small_font {font-size: 8pt;}
.gauge .gauge-bar {height: 14px;}
div.gauge
{
	width: 100%;
	background-color: white;
	border: 1px solid #666666;
}
div.gauge-bar
{
	background-color: #963812;
	text-align: right;
	color: white;
	font-size: 10px;
}
.list td, td.odd
{
	padding: 2px 3px 2px 3px;
	font-size: 10pt;
}
.disabled, .key .infocolor
{
	color: #b0b0b0;
}

.ads{
width:150px;
}

#room_list{
   background-color:#FFFFFF;
   padding:0px;
   margin:0px;
   width:515px;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
   border-top: 0px solid #C6C7CD;
}

#room_list table{
   border-collapse: collapse;
   border: 0px solid #C6C7CD;
}

.room_list_head{
    height:24px;
    background: url(menu_bg.gif) repeat-x;
}

#room_list .portal_section_title{
   xtext-transform:uppercase;
}

#room_list .portal_description{
   font-weight:normal;
}

#room_list .portal-head{
   background-color: #7DC242;
   font-size:8pt;
}

#room_list a.head{
   color: #FFFFFF;
   font-weight:bold;
}

#room_list .portal_link{
   color: #FFFFFF;
   font-weight:bold;
}

#room_list .portal-even, #portal_config_overview td.even{
   background-color: #F1EFE7;
}

#portal_config_overview td.head{
   background: url(menu_bg.gif) repeat-x;
   height:24px;
   color: #606060;
   padding: 2px 5px;
   font-weight:bold;
}

#portal_config_overview table.list{
   background-color: #FFFFFF;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
   margin:0px;
   padding:0px;
}

#portal_config_overview table.configuration_table{
    border:0px;
}



#room_list .gauge-bar{
    background-color: #7DC242;
}

#portal_search, #portal_action, #left_box, #portal_news, #portal_news2, #portal_announcements{
   background-color: #FFFFFF;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
   margin:0px;
   padding:0px;
}

#room_actions a{
  font-size:8pt;
}

#room_actions {
  font-size:8pt;
}

#left_box a.myarea_content, #portal_news a.myarea_content, #portal_news2 a.myarea_content{
   display: inline;
}

#portal_news .myarea_section_title, #portal_news2 .myarea_section_title{
   font-family: Helvetica, Verdana, sans-serif;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-top:0px;
   xtext-transform:uppercase;
   border-bottom: 1px solid #C6C7CD;
}

#left_box .myarea_section_title{
   font-family: Helvetica, Verdana, sans-serif;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-top:20px;
   xtext-transform:uppercase;
   border-bottom: 1px solid #C6C7CD;
}

#portal_room_config, #portal_config_overview{
   border: 1px solid #C6C7CD;
   padding:5px;
}

#portal_room_config div.right_box_title, #portal_config_overview div.right_box_title{
   background: url(menu_bg.gif) repeat-x;
   height:24px;
   color: #606060;
   padding: 2px 5px;
   font-weight:bold;
   font-size: 10pt;
}

#portal_room_config div.right_box, #portal_config_overview div.right_box{
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
}

#portal_room_config div.right_box_main, #portal_config_overview div.right_box_main{
  border:0px;
}

#portal_config_overview a.index_system_link, #portal_config_overview div.index_forward_links{
   color: #606060;
}

#portal_announcements .info-link-h1{
   font-family: Helvetica, Verdana, sans-serif;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
   background: url(menu_bg.gif) repeat-x;
}


.info-link-h1{
   font-family: Helvetica, Verdana, sans-serif;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
   background: url(menu_bg.gif) repeat-x;
}

.info-link-h2{
   font-family: Helvetica, Verdana, sans-serif;
   font-size: 12px;
   color: #606060;
   margin: 0px;
   line-height: 24px;
   padding-left:5px;
   xtext-transform:uppercase;
}

#portal_search .search_title{
   overflow:hidden;
}

#room_detail{
   width: 515px;
   background-color: #FFFFFF;
   margin-bottom:20px;
   border-left: 1px solid #C6C7CD;
   border-right: 1px solid #C6C7CD;
   border-bottom: 1px solid #C6C7CD;
}

#room_detail span.search_title{
   font-size:10pt;
   xtext-transform:uppercase;
}

#room_detail_actions{
   width:100%;
   text-align:right;
   font-size:8pt;
}

.anouncement_background{
   border-bottom:1px solid #C6C7CD;
}

body {
  margin: 0;
  padding: 0;
  background: #edf5fa;
  font: 12px/170% Verdana;
  color: #494949;
}

input {
  font: 12px/100% "Verdana";
  color: #494949;
}

textarea, select {
  font: 12px/160% "Verdana";
  color: #494949;
}

h1, h2, h3, h4, h5, h6 {
  margin: 0;
  padding: 0;
  font-weight: normal;
  font-family: Helvetica, Arial, sans-serif;
}

h1 {
  font-size: 170%;
}

h2 {

}

h3 {
  font-size: 140%;
}

h4 {
  font-size: 110%;
  font-weight: bold;
}

h5 {
  font-size: 120%;
}

h6 {
  font-size: 110%;
}

ul, quote, code {
  margin: .5em 0;
}

p {
  margin: 5px;
  padding: 0;
}

a:link, a:visited {
  color: #2b4812;
  text-decoration: none;
}

a:hover {
  color: #2b4812;
  text-decoration: underline;
}

a:active, a.active {
  color: #2b4812;
}

hr {
  margin: 0;
  padding: 0;
  border: none;
  height: 1px;
  background: #ccc;
}

ul {
  margin: 0.5em 0 1em;
  padding: 0;
}

ul li, ol li {
  margin: 0.4em 0 0.4em 0;
  list-style-position: inside;
  list-style-type: none;
  list-style-image: none;
}

ul.menu, .item-list ul {
  margin: 0.35em 0 0 -0.5em;
  padding: 0;
}

ul.menu ul, .item-list ul ul {
  margin-left: 0em;
}

ul.menu li, .item-list ul li, li.leaf {
  margin: 0.15em 0 0.15em .5em;
}

ul.menu li, .item-list ul li, li.leaf {
  padding: 0 0 .2em 0;
  list-style-type: none;
  list-style-image: none;
}

ul li.expanded {

}

ul li.collapsed {

}

ul li.leaf a, ul li.expanded a, ul li.collapsed a {
  display: block;
}

ul.inline li {
  background: none;
  margin: 0;
  padding: 0 1em 0 0;
}

fieldset ul.clear-block li {
  margin: 0;
  padding: 0;
  background-image: none;
}

dl {
  margin: 0.5em 0 1em 1.5em;
}

dl dt {
}

dl dd {
  margin: 0 0 .5em 1.5em;
}

img, a img {
  border: 0px;
}

.image image-thumbnail {
  border: 0px;
}

/************************** TABELLEN ****************/

table {
  margin: 1em 0;
  width: 100%;
}

thead th {
  border-bottom: 2px solid #ccc;
  color: #606060;
  font-weight: bold;
}

th a:link, th a:visited {
  color: #606060;
}

td, th {
  padding: .3em .5em;
}

tr.even, tr.odd {
  border: solid #ccc;
  border-width: 1px 0;
}

tr.odd, tr.info {
  background-color: #f7f7f7;
}

tr.even {
  background-color: #fff;
}

tr.odd td.active {
  background-color: #eee;
}

tr.even td.active {
  background-color: #fafafa;
}

td.region, td.module, td.container {
  border-top: 1.5em solid #fff;
  border-bottom: 1px solid #b4d7f0;
  background-color: #d4e7f3;
  color: #455067;
  font-weight: bold;
}

tr:first-child td.region, tr:first-child td.module, tr:first-child td.container {
  border-top-width: 0;
}

span.form-required {
  color: #ffae00;
}

span.submitted, .description {
  font-size: 0.92em;
  color: #898989;
}

.description {
  line-height: 150%;
  margin-bottom: 0.75em;
  color: #898989;
}

.messages, .preview {
  margin: .75em 0 .75em;
  padding: .5em 1em;
}

.messages ul {
  margin: 0;
}

.form-checkboxes, .form-radios, .form-checkboxes .form-item, .form-radios .form-item {
  margin: 0.25em 0;
}

.center form {
  margin-bottom: 2em;
}

.form-button, .form-submit {
  margin: 1em 0.5em 0 0;
}

#watchdog-form-overview .form-submit,
.confirmation .form-submit,
.search-form .form-submit,
.poll .form-submit,
fieldset .form-button, fieldset .form-submit,
.sidebar .form-button, .sidebar .form-submit,
table .form-button, table .form-submit {
  margin: 0;
}

.box {
  margin-bottom: 2.5em;
}

.view-Blogossphere-blogfeeds ul .primary {
  border: 1px solid #ffff00;
}

.view-content-Blogossphere-blogfeeds .content {
  clear: left;
  min-height: 85px;
  margin: 20px 0;
  text-align: justify;
  border-bottom: 1px solid #eee;
  padding-bottom: 20px;
}

#neuigkeiten .panel-col-1 .content, #portraits .panel-col-1 .content, #veranstaltung .panel-col-1 .content {
  clear: left;
  min-height: 85px;
  margin-bottom:  10px;
  text-align: justify;
  border-bottom: 1px solid #eee;
  padding-bottom: 20px;
}

.view-content-Blogossphere-blogfeeds .content .teasertext {
  margin-left: 115px;
}

.view-content-Blogossphere-blogfeeds-punkte .node {
  clear: left;
  min-height: 85px;
  margin: 20px 0;
}


/*** Local tasks */
ul.primary, ul.primary li {
  border: 0;
  background: none;
  margin: 0;
  padding: 0;
}

#tabs-wrapper {
  margin-left: -5px;
  margin-right: 70px;
  border-bottom: 1px solid #e9eff3;
  position: relative;
  font-size: 11px;
}

ul.primary {
  margin: 10px 0 0 0;
  padding-left: 5px;
  padding-bottom: 4px;
  float: left;
}

h2.with-tabs {
  float: left;
  margin: 0 2em 0 0;
  padding: 0;
}

ul.primary li a, ul.primary li.active a, ul.primary li a:hover, ul.primary li a:visited {
  border: 0;
  background: transparent;
  padding: 4px 1em;
  margin-right: 2px;
  height: auto;
  text-decoration: none;
  position: relative;
  background-color: #f7f7f7;
  border: 1px solid #eee;
  }

ul.primary li a:hover {
  color: #000;
}

ul.primary li.active a, ul.primary li.active a:link, ul.primary li.active a:visited {
  background-color: #fff;
  border: 1px solid #eee;
  border-bottom: 1px solid #fff;
  color: #000;
  margin-right: 3px;
}
ul.primary li.active a,
ul.secondary li.active a {
  font-weight: bold;
}

ul.secondary {
  clear: both;
  text-align: left;
  margin: 0 0 10px 0;
  padding: 0 0 10px 0;
  border-bottom: 1px solid #eee;
}

 ul.secondary li {
  display: inline-block;
  margin: 0;
  padding: 0;
  border: 0;
  min-width: 230px;
}

ul.secondary li a, ul.secondary li.active a, ul.secondary li a:hover, ul.secondary li a:visited {
  border: 0;
  background: transparent;
  padding: 4px;
  margin: 2px;
  text-decoration: none;
  position: relative;
}

ul.secondary li.active a, ul.secondary li.active a:link, ul.secondary li.active a:visited, ul.secondary li a:hover {
  color: #000;
}


/*** Nodes & comments */

ul.links li, ul.inline li {
  margin-left: 0;
  margin-right: 0;
  padding-left: 0;
  padding-right: 0;
  background-image: none;
}

.node .links, .comment .links {
  text-align: left;
}

.node .links ul.links li, .comment .links ul.links li {}
.terms ul.links li {
  margin-left: 0;
  margin-right: 0;
  padding-right: 0;
  padding-left: 1em;
}

.picture, .comment .submitted {
  float: right;
  clear: right;
  padding-left: 1em;
}

.new {
  color: #ffae00;
  font-size: 0.92em;
  font-weight: bold;
  float: right;
}

.terms {
  float: right;
}

.preview .node, .preview .comment, .sticky {
  margin: 0;
  padding: 0.5em 0;
  border: 0;
  background: 0;
}

.sticky {
  padding: 1em;
  background-color: #fff;
  border: 1px solid #e0e5fb;
  margin-bottom: 2em;
}

.indented {
  margin-left: 25px;
}

.node .content, _.c_omment .content {
  margin: 0 0 20px 0;
  font-weight: normal;
}

/*** Menu.module */

tr.odd td.menu-disabled {
  background-color: #edf5fa;
}
tr.even td.menu-disabled {
  background-color: #fff;
}
td.menu-disabled {
}

/*** Poll.module */

.poll .bar {
  background: #fff url(images/bg-bar-white.png) repeat-x 0 0;
  border: solid #f0f0f0;
  border-width: 0 1px 1px;
}

.poll .bar .foreground {
  background: #71a7cc url(images/bg-bar.png) repeat-x 0 100%;
}

.poll .percent {
  font-size: .9em;
}

/*** Autocomplete. */

#autocomplete li {
  cursor: default;
  padding: 2px;
  margin: 0;
}

/*** Syndication icons and block */

#block-node-0 h2 {
  float: left;
  padding-right: 20px;
}

#block-node-0 img, .feed-icon {
  float: right;
  padding-top: 4px;
}

#block-node-0 .content {
  clear: right;
}

.view-content {
  margin: 0;
}

/*** Login Block */

#user-login-form {
  text-align: left;
}
#user-login-form ul {
  text-align: left;
}

/*** Admin Styles */

div.admin-panel,
div.admin-panel .description,
div.admin-panel .body,
div.admin,
div.admin .left,
div.admin .right,
div.admin .expert-link,
div.item-list,
.menu {
  margin: 0;
  padding: 0;
}

div.admin .left {
  float: left;
  width: 48%;
}
div.admin .right {
  float: right;
  width: 48%;
}

div.admin-panel {
  background: #fff url(images/gradient-inner.png) repeat-x 0 0;
  padding: 1em 1em 1.5em;
}

div.admin-panel .description {
  margin-bottom: 1.5em;
}
div.admin-panel dl {
  margin: 0;
}
div.admin-panel dd {
  color: #898989;
  font-size: 0.92em;
  line-height: 1.3em;
  margin-top: -.2em;
  margin-bottom: .65em;
}

table.system-status-report th {
  border-color: #d3e7f4;
}

#autocomplete li.selected, tr.selected td, tr.selected td.active {
  background: #027ac6;
  color: #fff;
}

tr.selected td a:link, tr.selected td a:visited, tr.selected td a:active {
  color: #d3e7f4;
}

/*** CSS support */

span.clear {
  display: block;
  clear: both;
  height: 1px;
  line-height: 0px;
  font-size: 0px;
  margin-bottom: -1px;
}

/*******************************************************************
 * Color Module: Don't touch                                       *
 *******************************************************************/

/*** Generic elements. */

.messages {
  background-color: #fff;
  border: 1px solid #b8d3e5;
}

.preview {
  background-color: #fcfce8;
  border: 1px solid #e5e58f;
}

div.status {
  color: #3a3;
  border-color: #c7f2c8;
}

div.error {
  color: #c52020;
}

.form-item input.error, .form-item textarea.error {
  border: 1px solid #c52020;
  color: #494949;
}

/*** Watchdog.module */

tr.watchdog-user {
  background-color: #fcf9e5;
}

tr.watchdog-user td.active {
  background-color: #fbf5cf;
}

tr.watchdog-content {
  background-color: #fefefe;
}

tr.watchdog-content td.active {
  background-color: #f5f5f5;
}

tr.watchdog-warning {
  background-color: #fdf5e6;
}

tr.watchdog-warning td.active {
  background-color: #fdf2de;
}

tr.watchdog-error {
  background-color: #fbe4e4;
}

tr.watchdog-error td.active {
  background-color: #fbdbdb;
}

tr.watchdog-page-not-found, tr.watchdog-access-denied {
  background: #d7ffd7;
}

tr.watchdog-page-not-found td.active, tr.watchdog-access-denied td.active {
  background: #c7eec7;
}

/*** Status report colors. */

table.system-status-report tr.error, table.system-status-report tr.error th {
  background-color: #fcc;
  border-color: #ebb;
  color: #200;
}

table.system-status-report tr.warning, table.system-status-report tr.warning th {
  background-color: #ffd;
  border-color: #eeb;
}

table.system-status-report tr.ok, table.system-status-report tr.ok th {
  background-color: #dfd;
  border-color: #beb;
}

/* Bilder */

.upload-image-images .item-list ul li {
  background:none;
}

.upload-image-images .item-list ul li a {
  background:none;
  padding:0;
  border: 5px;
}

body {
  height: 100%;
  margin: 0 15px 0 0;
  padding: 0;
  color: #000;
  background-color: #fff;
  color: #494949;
  font: 80% 'lucida grande', tahoma, "ms sans serif", verdana, arial, sans-serif;
}

#header-region .block {
  display: block;
  margin: 0 1em;
}

#header-region .block-region {
  display: block;
  margin: 0 0.5em 1em;
  padding: 0.5em;
  position: relative;
  top: 0.5em;
}

#header-region * {
  display: inline;
  line-height: 1.5em;
  margin-top: 0;
  margin-bottom: 0;
}

input {
  font: 12px/100% "Verdana";
  color: #494949;
}

textarea, select {
  font: 12px/160% "Verdana";
  color: #494949;
}

h1, h2, h3, h4, h5, h6 {
  margin: 0;
  padding: 0;
  font-weight: normal;
  font-family: Arial, Helvetica, sans-serif;
}

#logos, #bildcontainer, #hintergrund {
  position: relative;
  float:left;
  width: 100%;
  min-width: 600px;
  max-width: 1000px;
  padding: 0 0 0 0;
}

#bildcontainer {
  position: relative;
  height: 120px;
  z-index:2;
}

.bild {
  position: absolute;
  left: 0;
  bottom: 0;
  width:100%;
  height: 120px;
  z-index:2;
}

.logolinks {
  height: 99px;
  width: 392px;
  background:url(logo_uhh_neu.gif) no-repeat;
  float:left;
  margin: 0;
}

.logorechts {
  height: 83px;
  width: 397px;
  background:url(epb_logo.gif) no-repeat;
  margin: 10px 25px 0 0;
  float:right;
}

.schattenlinks {
  float: none;
  clear: both;
  position:relative;
  background:url(leftshadow.png) repeat-y left;
  z-index:3;
}

.schattenrechts {
  position:relative;
  background:url(rightshadow.png) repeat-y right;
  z-index:3;
  height: 100%;
  margin: 0 20px 0 0;
}

#inhalt {
  float: none;
  clear: both;
  margin-left: 195px;
  z-index:5;
}

#brotkasten {
  position: absolute;
  height: 25px;
  width: 805px;
  bottom: 4px !important;
  bottom: 0;
  margin-left: 195px;
  z-index:4;
}

.breadcrumb1 {
  height: 24px;
  background-color: #fff;
  margin: 0px 4px;
  padding: 5px 0 0 18px;
  z-index:2;
}

.breadcrumb {
  font-size: 0.9em;
  margin: 0;
  padding-bottom: 0;
}

.breadcrumb a {
  color: #606060;
}

/*** Admin Styles */

div.admin-panel,
div.admin-panel .description,
div.admin-panel .body,
div.admin,
div.admin .left,
div.admin .right,
div.admin .expert-link,
div.item-list,
.menu {
  margin: 0;
  padding: 0;
}

div.admin .left {
  float: left;
  width: 48%;
}

div.admin .right {
  float: right;
  width: 48%;
}

div.admin-panel {
  background: #fff url(images/gradient-inner.png) repeat-x 0 0;
  padding: 1em 1em 1.5em;
}

div.admin-panel .description {
  margin-bottom: 1.5em;
}

div.admin-panel dl {
  margin: 0;
}

div.admin-panel dd {
  color: #898989;
  font-size: 0.92em;
  line-height: 1.3em;
  margin-top: -.2em;
  margin-bottom: .65em;
}

table.system-status-report th {
  border-color: #d3e7f4;
}

#autocomplete li.selected, tr.selected td, tr.selected td.active {
  background: #027ac6;
  color: #fff;
}

tr.selected td a:link, tr.selected td a:visited, tr.selected td a:active {
  color: #d3e7f4;
}

#lifelogo {
  position: absolute;
  background:url(life_logo.png) no-repeat !important;
  background:url(life_logo.gif) no-repeat;
  height: 110px;
  width: 76px;
  margin: 0px;
  top: 3px;
  right:15px;
  z-index:5;
  display: inline;
}

#hintergrund {
  background-color: #fff;
  background:url(grau.gif) repeat-x top;
  height: 100%;
  z-index: 1;
}

.clear {
  clear: left;
}

table {
  border-style: none;
}

.center {
  float: none;
  clear: both;
  background-color: #fff;
  border-left: 1px solid #fff;
  border-right: 1px solid #fff;
  margin: 0 4px 0 4px;
  padding: 5px 5px 0 17px;
}

.node h2 {
  margin: 0;
  padding: 0;
}

#footer {
  background-color: #fff;
  margin: 0 4px 0 4px;
  padding: 20px 0 15px 0;
  float: none;
  clear: both;
  z-index: 10;
}

/*******************************************************************
 VERTIKALES MENUE
 *******************************************************************/

#sidebar-left {
  position: absolute;
  left: -195px;
  top:0;
  width: 170px;
  height: 100%;
  margin: 10px;
}

.sidebar-left h2, .sidebar-left h3, #sidebar-left h2, #sidebar-left h3 {
  margin: 1.5em 0 0 0;
  font-size: 0.9em;
  border-bottom: 1px solid #808080;
  font-weight: bold;
}

.sidebar-left .form-text {
  margin: 0 0 0.3em 0;
}

#sidebar-left li.expanded a, .block-search h2 {
  border-bottom: solid 1px;
  padding: 1.4em 0 0.2em 0;
  margin: 0;
}

.block-search h2 {
  color: #606060;
}

#sidebar-left li.leaf a {
  border-bottom: solid 1px #ccc;
  padding: 0.3em 0.1em;
  margin: 0;
}

#sidebar-left li.leaf a:hover, #sidebar-left li.leaf a.active {
  color: #fff;
  background-color: #7cc242;
  text-decoration: none;
}

#sidebar-left a {
  display: block;
  margin: 0;
  color: #808080;
  font-size: 0.9em;
}

#sidebar-left ul, #sidebar-left ul .leaf {
  margin: 0;
  padding: 0;
}

#sidebar-left ul li, #sidebar-left li {
  list-style-type: none;
  list-style-image: none;
  display: inline;
  margin: 0;
  padding: 0;
}

#sidebar-left block-user ul li {
   list-style-type: none;
   list-style-image: none;
   display: block;
   padding: 0.3em 0.1em;
   margin: 0;
}

#block-block-3 a {
  float: right;
  padding: 5px;
}

#block-block-3 a:hover {
  color: #fff;
  background-color: #7cc242;
  text-decoration: none;
}

#user-login-form .form-item {
  font-size: 0.9em;
  font-weight: normal;
  margin: 5px 0;
}

#edit-submit .form-submit {
  margin: 0;
}


/*******************************************************************
 HORIZONTALE MENUES
 *******************************************************************/


/* oberes Menue */

#menu1 {
  margin: 0px 4px;
  background: url(menu_bg.gif) repeat-x;
  height: 24px;
}

#navlist {
  margin-left: 8px;
}

#navlist a {
  font-family: Helvetica, Verdana, sans-serif;
  font-size: 12px;
  color: #606060;
  margin: 0px;
  display: block;
  float: left;
  line-height: 24px;
  padding: 0 5px;
}

#navlist a:hover {
  background: url(menu_bg1.gif) repeat-x;
  text-decoration: none;
  color: #fff;
}

#navlist .menu-1-1-2-active, #navlist .menu-1-2-2-active, #navlist .menu-1-3-2-active, #navlist .menu-1-4-2-active, #navlist .menu-1-5-2-active, #navlist .menu-1-6-2-active, #navlist .menu-1-7-2-active {
  display: block;
  float: left;
  background: url(menu_bg2.gif) repeat-x;
  line-height: 24px;
  margin: 0;
  color: #000;
}


/* unteres Menue */

#menu2 {
  background-color: #cbf69c;
  line-height: 22px;
  margin: 0 4px;
}

#subnavlist {
  padding: 0 70px 0 15px;
}

#subnavlist a {
  font-family: Helvetica, Verdana, sans-serif;
  font-size: 11px;
  color: #000;
  margin: 0 2px;
}

#subnavlist a:hover {
  text-decoration: underline;
}

/* Bilder */

.upload-image-images .item-list ul {
background:none;
margin: 0;
padding: 0;
}

.upload-image-images .item-list ul li{
background:none;
margin: 0;
padding: 0;
}

.upload-image-images .item-list ul li a {
background:none;
margin: 0;
padding: 0;
border: 0px;
}

.view-header {
  margin: 0 0 20px 0;
}

#alles {
  margin: 0px auto;
  text-align: left;
  width: 1000px !important;
  width: 1000px;
}


.node .clear-block {
  display: none;
}



.ende {
  display: block;
}

.content img {
  margin: 2px 10px 0 0;
}

.bewertung {
  padding: 10px 0 0 0;
  clear: both;
}

.node h2 {
  font-size: 15px;
  font-weight: bold;
  text-align: left;
}

.ansicht {
  position: relative;
  min-height: 400px;
  z-index: 100;
}

.center h2 {
  border: 0;
  margin: 5px 0 0 0;
  padding: 0;
}

.center .panel-pane ul li {
  background: transparent url(icons/pfeil-r.gif);
  background-position: 0px 5px;
  background-repeat: no-repeat;
  font-size: 1em !important;
  padding-left: 0.6em;

}

/*******************************************************************
 PANELS
 *******************************************************************/

div.panel-pane div.node {
  margin: 0;
  padding: 0;
  border-bottom: 0px solid #eee;}

.panel-col-1 .panel-pane div.node {
  margin: 0;
  padding: 0;
  border-bottom: 0px solid #eee;
  text-align: justify;
}


div.panel-pane div.feed a {
  float: right;
}




.panel-pane .title {
  font: 12px/170% Verdana;
  margin: 0;
  background-color: #f7f7f7;
  padding: 0 0 0 4px;
  font-weight: bold;
  border-top: 2px solid #ccc;
  border-bottom: 1px solid #ccc;
  color: #404040;

}

.panel-pane {
  margin: 0 20px 10px 0 !important;
  margin: 0 10px 10px 0;
}

.panel-col-2 .panel-pane {
  margin: 0;
  padding-bottom: 10px;
}

.weitere {
  margin: 0 20px 30px 0;
  padding: 0 0 1em 0;
  border-bottom: 1px dotted #bbb;
}


#override .panel-col-only .field-item {
  margin: 0;
  padding: 0;
}

.panel-col-2 .inside {
  padding: 20px 0 0 0;
}

.panel-col-2 .panel-pane .content {
  background-color: #f7f7f7;
  padding: 8px;
  border: 1px solid #ccc;
  margin: 0;
}


.panel-col-2 .panel-pane .content .field-item a {
  text-align: justify;
  }

.panel-col-2 .panel-pane .content .content{
  border: 0;
  margin: 0;
  padding: 0;
}


.panel-col-2 .panel-pane .title, #override .panel-col-2 .panel-pane .title  {
  width: 140px;
  color: #fff;
  background: url(ro.png) no-repeat top right;
  background-color: #7cc242;
  font-size: 11px;
  font-family: sans-serif;
  font-weight: bold;
  padding: 0.3em 1em 0.3em 0.5em;
  border-bottom: 0px solid #fff;
  margin: 0;
  text-transform: none;
  border:0;
}

.panel-col-2 .panel-pane .node {
  margin: 0;
  padding: 0;
  border-bottom: 0px solid #eee;
}

.panel-col-2 .panel-pane h2 {
  margin: 0;
  padding: 0;
}

.panel-col-2 img {
  margin: 0;
  padding: 0;
}

.panel-col-2 ul .links li, .panel-col-2 ul, .panel-col-2 li {
  border: 0;
  margin: 0;
  background: none;
 }

#override .title {
  font-size: 15px;
  font-weight: bold;
  color: #2b4812;
  text-transform: none;
  margin: 0 0 0.5em 0;
  border-bottom: 0px solid #eee;
  background-color: transparent;
  border: 0;
}

.view-Veranstaltungen-weitere .view-data-node-title {
  margin-right: 10px;
  float: none;
}

.view-Veranstaltungen .node {
  clear: left;
  min-height: 85px;

}

.blogtitel {
  color: #008D00;
}

/*******************************************************************
 KOMMENTARE
 *******************************************************************/

 #kommentare .content {
  background-color: #f7f7f7;
  padding: 8px;
  border: 1px solid #ccc;
  margin: 0;
}

#kommentare .content .content, #kommentare .clear-block {
  border: 0;
  margin: 0;
  padding: 0;
}

#kommentare .title {
  width: 140px;
  color: #fff;
  background: url(ro.png) no-repeat top right;
  background-color: #7cc242;
  font-size: 11px;
  font-family: sans-serif;
  font-weight: bold;
  padding: 0.3em 1em 0.3em 0.5em;
  border-bottom: 0px solid #fff;
  margin: 0;
  text-transform: none;
}

#kommentare ul li {
  list-style-image: none;
  list-style-type: none;
  background: none;
}

#kommentare h3 {
  margin: 0;
  padding: 0;
}

#kommentare p {
  margin: 0.5em 0 0 0;
}

#kommentare .links {
  margin: 5px 0 10px 0;
}

#kommentare label {
  margin: 0 5px 0 0;
  display: inline;
}

#kommentare .form-text{
  display: block;
}

#panels-comment-form {
  margin: 0;
  padding: 0;
}

#panels-comment-form div {
  margin: 0;
  padding: 0;
}

#panels-comment-form div .form-item {
  margin: 0 0 5px 0;
  padding: 0;
}

.resizable-textarea .form-textarea {
  height: 50px;
}

#kommentare .collapsible {
  display: none;
}

/* Formatierungstips und Link zu Formatierungsoptionen unter Formularfeld ausblenden*/
#panels-comment-form .tips, #panels-comment-form a, #node-form .tips, _#_node-form a, #comment-form .tips, #comment-form a,
#privatemsg-new-form .tips, #privatemsg-new-form a {
  display: none;
}

#freunde span, #schaulustige span  {
  margin-right: 10px;
  padding: 8px;
  float: left;
  min-width: 220px;
}

#freunde img, #freunde p, #schaulustige img, #schaulustige p  {
  float: left;
}

#schaulustige {
  clear: left;
}

.profilteaser {
  display: inline-block;
  margin-right: 10px;
  padding: 8px;
  text-align: center;
  max-width: 15em;
}

.profilteaser img {
  display: block;
}

#gruppen .title, #aushaenge .title, #freunde .title, #schaulustige .title, #meinweblog .title, #meinegruppen .title, #fragen .title, #antworten .title, #zieleer .title, #zieleun .title, #zieleon .title, .meinefreunde .title {
  color: #808080;
  font-family: sans-serif;
  font-size: 12px;
  xtext-transform: uppercase;
  margin: 0 0 10px 0;
  padding: 0 0 0 4px;;
  border-bottom: 1px solid #eee;
  background-color: #f7f7f7;
}

#zieleun {
  clear: left;
}

#zieleun table, #zieleon table {
  margin: -15px 0 0 0;
  border: 0;
}

#zieleun tbody, #zieleon tbody {
  border: 0;
}

#zieleun th, #zieleon th  {
  border: 0;
}

#zieleun td, #zieleon td {
  background-color: #fff;
  border-top: 1px solid #fff;
  border-bottom: 1px solid #fff;
}

#zieleun label, #zieleon label  {
  display: none;
}



/* PROFILSTYLING*/

.listerechts .content p {
  margin: 4px 0 0 0;
  padding: 0 0 4px 0;
  border-bottom: 1px solid #eee;
}

.group-unter-welchem-namen-find {
  width: 300px;
  float: left;
  margin-right: 20px;
}

.group-unter-welchem-namen-find .form-item label {
  float: left;
  margin-right: 2px;
  width: 6em;
}

.group-unter-welchem-namen-find .form-item .form-text {
  width: 200px;
}

.group-profil-kontakt .form-item label {
  float: left;
  margin-right: 2px;
  width: 6em;
}

.group-profil-kontakt {
  width: 350px;
  float: left;
  margin-right: 20px;
}

  .group-profil-kontakt .form-item .form-text {
  width: 200px;
}

#field-profil-avatar-attach-wrapper {
  width: 200px;
}

.uebermich .form-item label {
  margin-right: 2px;
  width: 6em;
}

.uebermich .date-part {
  float: left;
}

.profilbox {
  width: 330px !important;
  width: 300px;
  float: left;
  margin: 10px;
  padding: 10px;
  background-color: #f7f7f7;
  border: 1px solid #eee;
}

.profilbox h3 {
  margin: 0;
}

.profilbox legend {
  display: none;
}


fieldset {
  border: 0;
  margin: 0;
}

#profil .form-text {
  width: 200px;
}

#profil .form-submit {
  margin-left: 20px;
}

.lieblinge .description {
  display: none;
}

.spalte1 {
  float: left;
  width: 375px;
}



#bildup .fieldset-wrapper {
  width: 300px;
}

#bildup img {
  display: block;
  clear: both;
}

#bildup .imagefield-edit-image-detail {
  margin: 10px 0 0 0;
}

.profilbox .description {
  margin-right: 10px;
}

.userpic {
background: url(ro.png) no-repeat top right;
z-index: 200;
}

.userpic img {
  position: relative;
  z-index: 99;
  width: auto;
  height: 50px;
}

.mitte {
  width: 50px;
  clear: both;
  margin: 0 auto 5px auto;
}

#profil .form-item {
  margin: 4px 0;
}

.clear-block {
  margin: 0 0 10px 0;
}

#meinweblog .content {
  line-height: 1.4em;
}

#ueberblog .content p {
  margin: 5px 0 0 0;
}

#mysite-sort0 {
  width: 496px;
}
#mysite-sort1 {
  width: 213px;
}

#mysite-sort1 span.mysite-header {
  width: 140px;
  color: #fff;
  background: url(ro.png) no-repeat top right;
  background-color: #7cc242;
  font-size: 11px;
  font-family: sans-serif;
  font-weight: bold;
  padding: 0.3em 1em 0.3em 0.5em;
  border-bottom: 0px solid #fff;
  margin: 0;
  text-transform: none;
}

.profilbild {
  float: left;
}

.clear {
  clear: both;
}

.taxover h3 {
  margin: 0 0 10px 0;
  border-bottom: 1px solid #eee;
}

_.taxover {
  display: inline-block;
  margin-right: 10px;
  padding: 8px;
  text-align: center;
  min-width: 100px;
}

.interessant p {
  margin: 0 0 5px 0;
}

.frage_icon {
  float: left;
  margin: -5px 10px -5px 0;
}

.frage_icon .content img {
  margin: 0;
  padding: 0;
}

#frage .node {
  padding: 0 0 0 10px;
}

#frage p {
  margin: 10px 0 0 0;
}


.miniicon {
  height: 30px;
  width: 30px;
  margin: 0 5px 0 0;
  float: left;
}



#community-tags-form .form-text {
  margin: 5px 0 0 0;
  width: 165px;
}


#profilbody .title, #profilbody p  {
  margin-left: 110px;
  text-align: left;
  border: 0;
}

#profilbody img {
  margin: -25px 10px 0 0;
  float: left;
}

#portraits h2 a {
  width: 383px;
  float: right;
}

#fokus .teasertext {
  margin: 0;
  font-size: 12px;
}

.teaserbild img {
  position: relative;
  width: 100px;
  height: auto !important;
  height: 75px;
  float: left;
  margin: 2px 10px 0 0 !important;
  margin: 2px 5px 0 0;
  top: 0;
  border: 1px solid #898989;
}

#teaser h2 a, #teaserfront h2 a, .teaser h2 a, .teaserfront h2 a {
  width: 383px !important;
  width: 430px;
  float: right;
  }

#fokus h2 a {
  width: 360px !important;
  width: 420px;
  float: right;
  color: #008D00;
}

.bodybild img {
  width: 200px;
  height: auto;
  float: left;
}


.meinblog img {
  float: left;
  margin: -10px 10px 0 0;
}

.meinblog h3, #bloghead {
  border: 0;
  font-size: 16px;
  color: #909090;
}

.einblog {
  clear: left;
  float: none;
}

.nichtblog {
  clear: left;
}

.miniiconblog {
  margin: -10px 10px 0 0;
  float: left;
}

.gross {
  min-height: 30px;
}



.view-Newsticker .view-data-node-title a {
  padding: 0;
  margin: 0;
}

.view-footer-Newsticker {
  margin: 0 0 0 0.3em;
  padding: 0;
}

#startseite .panel-col-1 .panel-pane .title {
  background-color: #fff;
  color: #202020;
  border: 0;
  xtext-transform: uppercase;
  font-size: 11px;
}


#ticker h2.title, #waslos h2.title  {
  background-color: #fff;
  color: #808080;
  border-top: 0;
  border-bottom: 1px solid #808080;
}

#ticker .view-data-node-title {
  font-size: 14px;
  font-weight: bold;
  padding-left: 0.5em;
}

#fokus, #ticker, #waslos {
  padding: 0 0 1em 0;
  margin: 0;
  border-bottom: 1px dotted #ccc;
}

#ticker {
  padding-top: 0.8em;
  border-top: 1px dotted #ccc;
}

#fokus .panel-pane, #ticker .panel-pane, #waslos .panel-pane {
  padding: 0 0 1em 0;
  margin: 0;

}

#startseite .panel-col-1 #fokus .panel-pane .title {
  border: 0;
  background-color: #f7f7f7;
  margin: 0;
  padding: 0.5em 0.8em 0 1em;
  border-right: 1px solid #ccc;
  border-left: 1px solid #ccc;
  border-top: 1px solid #ccc;
  font-size: 0.8em;
  xtext-transform: uppercase;
}

#ticker h2.title, #waslos h2.title {
  border: 0;
  background-color: none;
  margin: 0 0.5em 0 0.5em;
  padding: 0;
  font-size: 0.8em;
  xtext-transform: uppercase;
  color: #404040;
}

_#fokus h2 {
  margin-top: 0;
  padding-top: 0;
}


#fokus .content {
  margin: 0;
  padding: 0 0.8em 0.8em 0.8em;
  background-color: #f7f7f7;
  border-right: 1px solid #ccc;
  border-bottom: 1px solid #ccc;
  border-left: 1px solid #ccc;
  border-top: 1px solid #ccc;


}

#fokus .content .content {
  border: 0;
  margin: 0;
  padding:0;
}

/***** Vorschau-Button ausblenden (Kommentarfunktion) ***********/
#edit-preview {
  display: none;
}

.datum {
  float: right;
}

.uniblogstab {
}

.letztefragen p {
  margin: 0 0 10px 0;
}

.about, .about p {
  margin: 10px 0 0 0;
}

.ichmich {
  padding: 10px 0 0 0;
  clear: left;
 }


/******* LISTEN MIT BILD IN COL2 **************/

.listemitbild {
  border-bottom: 1px solid #eee;
  clear: left;
  min-height: 70px;
}

.listemitbild img {
  height: auto;
  width: 50px;
  float: left;
}

.listemitbild p {
  margin-left: 60px;
}

.listemitbild .voll p {
  margin: 0;
}

/********** MEINE INHALTE *************/

#meinweblog img {
  margin: -8px 10px 0 0;
  float: left;
}

#meinweblog h3, #meinweblog h2 {
  margin: 15px 0 -5px 80px;
  font-size: 14px;
  color: #909090;
  border: 0;
}

#meinweblog p {
  margin-left: 80px;
  font-weight: bold;
}

#zieleun .form-submit, #zieleon .form-submit  {
  font-size: 0.8em;
  margin-left: 270px;
}

#zieleun .view-field-node-title, #zieleon .view-field-node-title {
  width: 240px;
}

#profil legend {
  display: none;
}

.freundefreunde {
  padding: 20px 0 0 0;
  clear: left;
}

#edit-mark-read, #edit-mark-unread {
  display: none;
}

/***** IE - Abstand oben *******/

.aushang_verkaufe h2 {
  margin-top: inherit !important;
  margin-top: 10px;
}

#aushaenge .panel-pane {
  padding-bottom: 20px;
  float: none;
  height: 200px;
}

#aushaenge span .aushang_details {
  position: relative;
  width: 200px;
  background: #eee;
  border: 2px solid #ccc;
  padding: 5px;
  z-index: 1000;
  text-align: left;
}

.aushang_suche {
  background:url(post_gruen.png) no-repeat top left;
  height: 140px;
  width: 125px;
  margin: 5px;
  padding: 5px 10px 5px 15px !important;
  padding: 10px 10px 5px 15px;
  float: left;
}

.aushang_verkaufe {
  background:url(post_blau.png) no-repeat top left;
  height: 140px;
  width: 120px;
  margin: 5px;
  padding: 5px 10px 5px 15px !important;
  padding: 20px 10px 0 15px;
  float: left;
}

.aushang_biete {
  background:url(post_gelb.png) no-repeat top left;
  height: 140px;
  width: 125px;
  margin: 5px;
  padding: 5px 10px 5px 15px;
  float: left;
}

.aushang_sonstiges {
  background:url(post_lila.png) no-repeat top left;
  height: 140px;
  width: 125px;
  margin: 5px;
  padding: 5px 10px 5px 15px;
  float: left;
}

.aushang_suche span, .aushang_biete span, .aushang_verkaufe span, .aushang_sonstiges span {
  position: relative;
  visibility: hidden;
}

.aushang_suche:hover span, .aushang_biete:hover span, .aushang_verkaufe:hover span, .aushang_sonstiges:hover span {
  position: relative;
  visibility: visible;
}



#override .bookmarking .content ul li {
  list-style-type: none;
  list-style-image: none;
}

.meineziele label {
  display: none;
}

.einer {
  float: left;
  margin: 5px 10px 10px 0;
  padding: 8px;
  text-align: center;
  width: 180px;
  height: 80px;
  vertical-align: bottom;
}


.einer img {
  width: 50px;
  height: auto;
}

#freunde, #schaulustige, #fragen {
  clear: left;
}

.view-content-Neuigkeiten .node .content {
  clear: left;
  text-align: justify;
}

#teaser .view-content-Neuigkeiten .node, .teaser .view-content-Neuigkeiten .node  {
  padding-bottom: 10px !important;
  padding-bottom: 20px;
}

#profil .link-field-title .form-text, #node-form .link-field-title .form-text {
  width: 200px;
  margin-right: 5px;
}

#profil .link-field-row, #node-form .link-field-row {
  height: 20px;
}

#edit-field-profil-avatar-upload .form-text {
  width: 100px;
  border: 1px solid red;
}


#mediennutzung .content {
  min-height: 220px;
}

#browserhinweis {

  border: 1px solid red;
  width: 650px;
  padding: 7px;
}

tbody, th {
  border: 0;
}

.view-cell-header {
  border: 0;
}

#fragen, #schaulustige {
  padding-top: 20px;
}

h3.field-label {
  border: 0;
}

.liste p {
  border-bottom: 1px solid #ccc;
  padding-bottom: 6px;
}

.description #hinweis {
  font-size: 12px;
  color: #ff0000;
}

.view-content-stellenboerse {
  font-size: 0.9em;
}

#neuigkeiten .teasertext, #portraits .teasertext {
    margin-left: 115px;
}

#waslos img {
  width: 30px;
  height: auto;
  max-height: 30px;
  float: left;
  padding:  0.5em 0 0.5em 0
}

#waslos .waslos {
  padding: 5px;
  min-height: 30px;

}

#waslos .waslos p {
  margin: 0;
  padding: 0.5em 0 0 0;
  min-height: 35px;
}

.waslos .daslos {
  padding: 0.5em 0 0.5em 35px;

}

.view-Portraits-last p {
  margin: 0;
}

.view-footer-aushang p {
  clear: left;
}

.view-content-Newstickerarchiv .node {
  padding: 1em 0 0 0;
  margin: 0 0 0;
  border-top: 1px dotted #ccc;
  font-size: 12px;
  width: 500px;
  text-align: justify;
}

.view-content-Newstickerarchiv .node .content {
  padding: 0;
  margin: 0;
}

.view-content-Newstickerarchiv .node p {
  margin-left: 0;
  padding-left: 0;
}

.view-content-Newstickerarchiv .node img {
  margin: 0 0.1em;
}

#inhalt h3 {
  font-size: 1.1em;
  border: 0;
}

#profile_title, .profile_title {
   background: url(menu_bg.gif) repeat-x;
   color: #000;
   line-height:24px;
   font-size:12pt;
   margin:0;
   font-weight:bold;
   padding:0px 5px 0px 15px;
}

#profile_content {
   border:2px solid #ccc;
   margin-bottom:20px;
}

#profile_tabs_frame {
   background-color:#EEEEEE;
   border-bottom:1px solid #ccc;
   margin:0;
   padding:3px 10px;
   position:relative;
}

.profile_tab {
   border-right:1px solid #ccc;
   display:inline;
   padding:3px 10px;
}

.profile_tab_current {
   border-right:1px solid #ccc;
   display:inline;
   font-weight:bold;
   padding:3px 10px;
}

#p_announcements a:hover{
	text-decoration:none;
}

.list td.foot_right {
   background:#ccc;
   border-bottom:medium none;
   color:#000;
   font-weight:bold;
   padding:2px;
   text-align:right;
}

.list td.foot_left {
   background:#ccc;
   border-bottom:medium none;
   color:#000;
   font-weight:bold;
   padding:2px;
}

span.select_link {
color:#000;
font-size:8pt;
}
