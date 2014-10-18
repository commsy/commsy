<?php
// $Id$
//
// Release $Name$
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



/* 
----------------------------------------------------------------------
build in: July 2012
copyright: Kai Mertens, banality GmbH
location: Essen-Germany, www.banality.de
----------------------------------------------------------------------
*/



/*
----------------------------------------------------------------------
global structure - tag definitions
----------------------------------------------------------------------
*/

body {
    margin: 0px;
    border: 0px none;
    padding: 40px 0px 60px 0px;
    background: url(../img/body_bg.jpg) #080300;
    color: #5C4B36;
    font-family: verdana, sans-serif;
    text-align: center;
    font-size: 11px;
    line-height: 15px;
    }

p, form {
    margin: 0px;
    border: 0px none;
    padding: 0px;
    }

img {
    border: 0px none;
    }

div#top_menu {
	margin-top: -40px;
}
   
   
/*
----------------------------------------------------------------------
header + image rotator
----------------------------------------------------------------------
*/ 

.clear {
    clear: both;
    } 

#wrapper {
    width: 980px;
    margin: 0px auto;
    text-align: left;
    }

#header {
    background: url(../img/header_bg.jpg) repeat-x;
    height: 70px;
    }

#header img {
    float: left;
    padding: 10px 0px 0px 20px;
    }

#header ul {
    padding: 42px 12px 0px 0px;
    margin: 0px;
    float: right;
    }  
    
#header li {
    display: inline;
    padding: 0px 8px;
    font-size: 13px;
    }    

#header li a {
    color: #1F1A17;
    text-shadow: #FFF 0px 1px;
    text-decoration: none;
    }    

#header li a:hover, #header li a#active:hover {
    color: #838281;
    text-shadow: #FFF 0px 1px;
    text-decoration: none;
    }
        
#header li a#active {
    color: #D8261D;
    text-shadow: #FFF 0px 1px;
    text-decoration: none;
    }  
    
#claim {
    margin-top: 15px;
    margin-bottom: -13px;
    }    
    
#image_rotator {
    background: #FFF;
    padding: 10px;
    }    
    
#ir_navigation {
    text-align: center;
    padding: 6px 0px 7px 0px;
    }    
 
 
 
/*
----------------------------------------------------------------------
maincontent
----------------------------------------------------------------------
*/  
 
 
#maincontent {
    background: #E7EEF3;
    margin-bottom: 10px;
    padding: 0px 0px 30px 20px;
    }
 
.column_01 {
    float: left;
    width: 240px;
    }  

.column_02 {
    float: left;
    width: 720px;
    } 

.column_content {
    background: url(../img/column_content_bg.jpg) repeat-x;
    margin-right: 20px;
    padding: 10px 7px;
    }

#maincontent h2 {
    font-size: 13px;
    font-weight: normal;
    padding: 30px 0px 10px 10px;
    margin: 0px;
    color: #1F1A17;
    text-shadow: #FFF 0px 1px;
    border-bottom: 1px solid #CEC1B2;
    margin: 0px 20px 2px 0px;
    }

div.column_content h2 {
	padding: 0px 0px 10px 10px !important;
}

#maincontent .column_01 ul {
    border-top: 1px dashed #CEC1B2;
    margin: 20px 0px 0px 0px;
    padding: 5px 0px 0px 0px;
    }

#maincontent .column_01 ul li {
    list-style-type: none;
    background: url(../img/bullet_point.jpg) no-repeat 0px 6px;
    padding: 0px 0px 0px 15px;
    }

.cc_prest_items {
    border-bottom: 1px dashed #CEC1B2;
    margin: 20px 0px;
    }

.cc_prestige {
    border-top: 1px dashed #CEC1B2;
    background: url(../img/bullet_point.jpg) no-repeat 0px 11px;
    display: block;
    padding: 5px 0px 5px 15px;
    }

.column_01 form label {
    font-weight: bold;
    }

.field_item input {
    border: 1px solid #CEC1B2;
    background: url(../img/form_input_bg.jpg) repeat-x;
    width: 190px;
    padding: 5px;
    margin: 5px 0px;
    }

.form_actions {
    padding: 5px 0px 0px 0px;
    }

.form_actions input {
    border: 1px solid #FFF;
    background: url(../img/button_input_bg.jpg) repeat-x;
    color: #235D96;
    padding: 5px;
    }

#maincontent a {
    color: #D8261D;
    text-decoration: none;
    }

#maincontent a:hover {
    color: #D8261D;
    text-decoration: underline;
    }


 
/*
----------------------------------------------------------------------
footer
----------------------------------------------------------------------
*/ 
 
#footer {
    color: #E1E1E1;
    }    

#footer h2 {
    font-size: 11px;
    font-weight: normal;
    padding: 0px 0px 5px 0px;
    margin: 0px;
    text-align: center;
    }
    
#partner_area {
    background: url(../img/partner_area_bg.jpg) repeat-x;
    margin-bottom: 25px;
    }    

#partner_area ul {
    padding: 0px;
    margin: 0px;
    }
    
#partner_area li {
    list-style-type: none;
    float: left;
    width: 245px;
    text-align: center;
    }    

#footer_navigation {
    float: right;
    }    

#footer_navigation a {
    color: #E1E1E1;
    text-decoration: none;
    }  
    
#footer_navigation a:hover {
    color: #CD6565;
    text-decoration: none;
    }    
        
        
        
/*
----------------------------------------------------------------------
Raum-Liste
----------------------------------------------------------------------
*/        
               
.portal-head {
    background: #DB261D;
    padding: 5px;
    text-transform: uppercase;
    color: #FFF !important;
    }     
        
.portal-head a {
    text-transform: uppercase;
    color: #FFF !important;
    }    

.portal-odd {
    background: #FFF;
    padding: 5px 0px;
    }   
    
.portal-even {
    padding: 5px 0px;    
    }

.portal-odd img, .portal-even img {
    padding: 0px 5px;
    }   



/*
----------------------------------------------------------------------
Signup-Tabelle
----------------------------------------------------------------------
*/

#signup_table {
    margin: 30px 0px 15px 0px;
    }

#signup_table td {
    width: 250px;
    }

#signup_table input {
    width: 190px;
    border: 1px solid #CEC1B2;
    background: url(../img/form_input_bg.jpg) repeat-x;
    padding: 5px;
    margin: 2px 0px 10px 0px;
    }

#signup_table label {
    font-weight: bold;
    display: block;
    }

.field_help {
    color: #729EC3;
    font-style: italic;
    }

#commsy_legal {
    overflow: auto;
    height: 200px;
    background: url(../img/form_input_bg.jpg) repeat-x #FFF;
    padding: 10px;
    margin: 0px 0px 30px 0px;
    }

#commsy_legal h4 {
    font-size: 12px;
    font-weight: bold;
    padding: 20px 0px 10px 0px;
    margin: 20px 0px 0px 0px;
    border-top: 1px solid #CEC1B2;
    }

#portal_room_config td{
   padding:5px;
   }

span.key{
   padding:5px;
}

.gauge{
    border:1px solid #ddd;
}

.gauge-bar{
    background-color: #ddd;
}

/* Profile Tab Style */

#profile_tabs_frame {
   position:relative;
   padding:3px 10px;
   margin:0px 0px 0px 0px;
   background-color: #EEEEEE;
   border-bottom:1px solid <?php #echo($color['tabs_background'])?>;
}

#profile_tablist{
    margin:0px;
    white-space:nowrap;
    display:inline;
}

.profile_tab{
    border-right:1px solid <?php #echo($color['tabs_background'])?>;
    padding:3px 10px;
    display:inline;
}

.profile_tab_current{
    border-right:1px solid <?php #echo($color['tabs_background'])?>;
    padding:3px 10px;
    display:inline;
    font-weight:bold;
}

#profile_title, .profile_title{
   background:url(images/detail_fader_<?php #echo($color['schema'])?>.gif) center repeat-x;
   background-color:<?php #echo($color['tabs_background'])?>;
   color:<?php #echo($color['headline_text'])?>;
   vertical-align:top;
   margin:0px;
   padding:5px 10px;
   font-size: 14pt;
}

#profile_content{
   margin-bottom:20px;
   padding:0px
   background-color: #FFFFFF;
   border: 2px solid <?php #echo($color['tabs_background'])?>;
}

a.titlelink{
   color:<?php #echo($color['headline_text'])?>;
}

