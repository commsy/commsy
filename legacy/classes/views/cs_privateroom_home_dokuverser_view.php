<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

$this->includeClass(VIEW);
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_privateroom_home_dokuverser_view extends cs_view {

var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_DOKUVERSER');
      $this->setViewName('clock');
   }

   function getPortletJavascriptAsHTML(){
     $current_context_item = $this->_environment->getCurrentContextItem();
     $color_array = $current_context_item->getColorArray();
     unset($current_context_item);
     $params = array();
     $params = $this->_environment->getCurrentParameterArray();
     $params['output'] = 'XML';
     $params['SID'] = $this->_environment->getSessionID();
     $data_url = utf8_encode(rawurlencode(_curl(false,$this->_environment->getCurrentContextID(),'material','index',$params)));
     unset($params);
     $height = '450';
     $bgcolor = '#ffffff';
     unset($color_array);
     $html  = '';
#     $html .='<embed style="height:450px; text-align:center; width:100%;" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" allowscriptaccess="sameDomain" name="study_log" bgcolor="#ffffff" quality="high" wmode="transparent" id="study_log" flashvars="thickbox=false&amp;thickboxHeight=550&amp;motionSpeed=18&amp;thumbWidth=10&amp;applicationType=commsy&amp;commsyXml=commsy.php%3Fcid%3D196732%26mod%3Dmaterial%26fct%3Dindex%26output%3DXML%26SID%3Dcd2fcd129ab49aef84f2058ab3880f7f" src="flash/study_log.swf">'.LF;
     $html .= '<script src="javascript/AC_OETags.js" language="javascript" type="text/javascript"></script>'.LF;
     $html .= '<script src="javascript/history/history.js" language="javascript" type="text/javascript"></script>'.LF;
     $html .= '<script language="JavaScript" type="text/javascript">'.LF;
     $html .= '<!--'.LF;
     $html .= '// Version check for the Flash Player that has the ability to start Player Product Install (6.0r65)'.LF;
     $html .= 'var hasProductInstall = DetectFlashVer(6, 0, 65);'.LF;
     $html .= '// Version check based upon the values defined in globals'.LF;
     $html .= 'var hasRequestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);'.LF;
     $html .= 'if ( hasProductInstall && !hasRequestedVersion ) {'.LF;
     $html .= '    // DO NOT MODIFY THE FOLLOWING FOUR LINES'.LF;
     $html .= '    // Location visited after installation is complete if installation is required'.LF;
     $html .= '    var MMPlayerType = (isIE == true) ? "ActiveX" : "PlugIn";'.LF;
     $html .= '    var MMredirectURL = window.location;'.LF;
     $html .= '    document.title = document.title.slice(0, 47) + " - Flash Player Installation";'.LF;
     $html .= '    var MMdoctitle = document.title;'.LF;
     $html .= '    var MMdoctitle = document.title.slice(0, 47) + " - Flash Player Installation";'.LF;
     $html .= '    jQuery("#docuverser").append(AC_FL_RunContent('.LF;
     $html .= '        "src", "flash/playerProductInstall",'.LF;
     $html .= '        "FlashVars", "MMredirectURL="+MMredirectURL+\'&MMplayerType=\'+MMPlayerType+\'&MMdoctitle=\'+MMdoctitle+\'&commsyXml=\'+\''.$data_url.'\'+\'&applicationType=commsy\'+"",'.LF;
     $html .= '        "width", "100%",'.LF;
     $html .= '        "height", "'.$height.'px;",'.LF;
     $html .= '        "align", "middle",'.LF;
     $html .= '        "id", "study_log",'.LF;
     $html .= '        "wmode", "transparent",'.LF;
     $html .= '        "quality", "high",'.LF;
     $html .= '        "bgcolor", "'.$bgcolor.'",'.LF;
     $html .= '        "name", "study_log",'.LF;
     $html .= '        "allowScriptAccess","sameDomain",'.LF;
     $html .= '        "type", "application/x-shockwave-flash",'.LF;
     $html .= '        "pluginspage", "http://www.adobe.com/go/getflashplayer"'.LF;
     $html .= '    ));'.LF;
     $html .= '} else if (hasRequestedVersion) {'.LF;
     $html .= '    // if we\'ve detected an acceptable version'.LF;
     $html .= '    // embed the Flash Content SWF when all tests are passed'.LF;
     $html .= '    jQuery("#docuverser").append(AC_FL_RunContent('.LF;
     $html .= '            "src", "flash/study_log",'.LF;
     $html .= '            "FlashVars", "thickbox=false&thickboxHeight=550&motionSpeed=18&thumbWidth=14&applicationType=commsy&commsyXml='.$data_url.'",'.LF;
     $html .= '            "width", "100%",'.LF;
     $html .= '            "height", "'.$height.'px;",'.LF;
     $html .= '            "align", "middle",'.LF;
     $html .= '            "id", "study_log",'.LF;
     $html .= '            "wmode", "transparent",'.LF;
     $html .= '            "quality", "high",'.LF;
     $html .= '            "bgcolor", "'.$bgcolor.'",'.LF;
     $html .= '            "name", "study_log",'.LF;
     $html .= '            "allowScriptAccess","sameDomain",'.LF;
     $html .= '            "type", "application/x-shockwave-flash",'.LF;
     $html .= '            "pluginspage", "http://www.adobe.com/go/getflashplayer"'.LF;
     $html .= '    ));'.LF;
     $html .= '  } else {  // flash is too old or we can\'t detect the plugin'.LF;
     $html .= '    var alternateContent = \'This content requires the Adobe Flash Player. <a href="http://www.adobe.com/go/getflash/">Get Flash</a>;\''.LF;
     $html .= '    jQuery("#docuverser").append(alternateContent);  // insert non-flash content'.LF;
     $html .= '  }'.LF;
     $html .= '// -->'.LF;
     $html .= '</script>'.LF;
     return $html;
   }



   function asHTML () {
     $html  = '';
     $html .= '<div id="'.get_class($this).'" style="padding-bottom:5px;">'.LF;
     $html .= ' <span style="margin-bottom:0px; margin-top:0px; font-weight:bold;">'.$this->_translator->getMessage('COMMON_SORTING_BOX').':</span> '.LF;
     $html .= '<a href="javascript:callStudyLogSortAlphabetical()">'.$this->_translator->getMessage('COMMON_TITLE').'</a>'.LF;
     $html .= ' | <a href="javascript:callStudyLogSortChronological()">'.$this->_translator->getMessage('COMMON_CALENDAR_DATE').'</a>'.LF;
     $html .= ' | <a href="javascript:callStudyLogSortDefault()">'.$this->_translator->getMessage('COMMON_NO_SORTING').'</a>'.LF;
     $html .= '</div>'.LF;
     $current_context_item = $this->_environment->getCurrentContextItem();
     $color_array = $current_context_item->getColorArray();
     unset($current_context_item);
     $params = array();
     $params = $this->_environment->getCurrentParameterArray();
     $params['output'] = 'XML';
     $params['SID'] = $this->_environment->getSessionID();
     $data_url = utf8_encode(rawurlencode(_curl(false,$this->_environment->getCurrentContextID(),'material','index',$params)));
     unset($params);
     $height = '450px';
     $bgcolor = '#ffffff';
     unset($color_array);

#     $html .= '<div class="index_flash" style="height: '.$height.'; background-color: '.$bgcolor.';">'.LF;
     $html .= '<div id="docuverser" style="height: '.$height.'; background-color: '.$bgcolor.';">'.LF;
     // jQuery
     //$html .= '<script src="javascript/studylog_flash.js" language="javascript" type="text/javascript"></script>'.LF;
     // jQuery
     $html .= '<noscript>'.LF;
     $html .= '    <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'.LF;
     $html .= '            id="study_log" width="100%" height="'.$height.'"'.LF;
     $html .= '            codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">'.LF;
     $html .= '            <param name="movie" value="flash/study_log.swf" />'.LF;
     $html .= '            <param name="quality" value="high" />'.LF;
     $html .= '            <param name="FlashVars" value="applicationType=commsy&amp;commsyXml='.$data_url.'" />'.LF;
     $html .= '            <param name="wmode" value="transparent" />'.LF;
     $html .= '            <param name="bgcolor" value="'.$bgcolor.'" />'.LF;
     $html .= '            <param name="allowScriptAccess" value="sameDomain" />'.LF;
     $html .= '            <embed src="study_log.swf" quality="high" bgcolor="'.$bgcolor.'"'.LF;
     $html .= '                width="100%" height="'.$height.'" name="study_log" align="middle"'.LF;
     $html .= '                play="true"'.LF;
     $html .= '                FlashVars="applicationType=commsy&amp;commsyXml='.$data_url.'"'.LF;
     $html .= '                wmode="transparent"'.LF;
     $html .= '                loop="false"'.LF;
     $html .= '                quality="high"'.LF;
     $html .= '                allowScriptAccess="sameDomain"'.LF;
     $html .= '                type="application/x-shockwave-flash"'.LF;
     $html .= '                pluginspage="http://www.adobe.com/go/getflashplayer">'.LF;
     $html .= '            </embed>'.LF;
     $html .= '    </object>'.LF;
     $html .= '</noscript>'.LF;
     $html .= '</div>'.LF;
     return $html;
   }
}
?>