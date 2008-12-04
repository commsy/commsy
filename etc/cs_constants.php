<?PHP
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

$interval = 20;
define('CS_LIST_INTERVAL',20);
$no_room = 0;
define('CS_NO_ROOM',0);
define('CS_ROOM_OPEN',1);
define('CS_ROOM_CLOSED',2);
define('CS_ROOM_LOCK',3);
$text='';
for($i=0;$i<40;$i++){
   $text.='&nbsp;';
}
define('CS_WITDH_CONSTANT',$text);
define('CS_ALL','all');

define('CS_ITEM_TYPE','item');

define('CS_MATERIAL_TYPE','material');
define('CS_INSTITUTION_TYPE','institution');
define('CS_TOPIC_TYPE','topic');
define('CS_ANNOUNCEMENT_TYPE','announcement');
define('CS_ANNOTATION_TYPE','annotation');
define('CS_USER_TYPE','user');
define('CS_TODO_TYPE','todo');
define('CS_DATE_TYPE','date');
define('CS_DISCUSSION_TYPE','discussion');
define('CS_GROUP_TYPE','group');
define('CS_SECTION_TYPE','section');
define('CS_DISCARTICLE_TYPE','discarticle');
define('CS_TASK_TYPE','task');
define('CS_BUZZWORD_TYPE','buzzword');
define('CS_TAG_TYPE','tag');
define('CS_TAG2TAG_TYPE','tag2tag');

define('CS_ROOM_TYPE','room');
define('CS_COMMUNITY_TYPE','community');
define('CS_PRIVATEROOM_TYPE','privateroom');
define('CS_GROUPROOM_TYPE','grouproom');
define('CS_MYROOM_TYPE','myroom');
define('CS_PROJECT_TYPE','project');
define('CS_PORTAL_TYPE','portal');
define('CS_SERVER_TYPE','server');

define('CS_FILE_TYPE','file');
define('CS_LABEL_TYPE','label');
define('CS_LINK_TYPE','link');
define('CS_LINKITEM_TYPE','link_item');
define('CS_LINKMODITEM_TYPE','link_modifier_item');
define('CS_LINKITEMFILE_TYPE','link_item_file');
define('CS_READER_TYPE','reader');
define('CS_NOTICED_TYPE','noticed');
define('CS_AUTH_SOURCE_TYPE','auth_source');

define('CS_TIME_TYPE','time');

define('CS_LOG_TYPE','log');
define('CS_LOGARCHIVE_TYPE','log_archive');
define('CS_LOG_ERROR_TYPE','log_error');

define('CS_WIKI_TYPE','wiki');

define('CS_HOMEPAGE_TYPE','homepage_page');
define('CS_LINKHOMEPAGEHOMEPAGE_TYPE','homepage_link_page_page');

$xml_string = '<RUBRIC_CONNECTIONS>
               <MATERIAL>
                  <TOPIC>false</TOPIC>
                  <INSTITUTION>false</INSTITUTION>
               </MATERIAL>
               <TOPIC>
                  <MATERIAL>false</MATERIAL>
                  <INSTITUTION>false</INSTITUTION>
               </TOPIC>
               <INSTITUTION>
                  <MATERIAL>false</MATERIAL>
                  <TOPIC>false</TOPIC>
               </INSTITUTION>
               <ANNOUNCEMENT>
                  <MATERIAL>false</MATERIAL>
                  <TOPIC>false</TOPIC>
                  <INSTITUTION>false</INSTITUTION>
               </ANNOUNCEMENT>
               <GROUP>
               </GROUP>
               <NEWS>
                  <MATERIAL>false</MATERIAL>
                  <GROUP>false</GROUP>
               </NEWS>
               <DATE>
                  <MATERIAL>false</MATERIAL>
                  <GROUP>false</GROUP>
               </DATE>
               <DISCUSSION>
                  <GROUP>false</GROUP>
               </DISCUSSION>
               <DISCARTICLE>
                  <MATERIAL>false</MATERIAL>
               </DISCARTICLE>
               </RUBRIC_CONNECTIONS>';
define('CS_RUBIC_CONNECTIONS',$xml_string);

//old colors
$cs_color['background']       = '#f5f2ec';
$cs_color['text']             = '#000000';
$cs_color['static_text']      = '#444444';
$cs_color['hyperlink']        = '#003366';
$cs_color['link_disabled']    = '#555555';
$cs_color['frozen']           = '#555555';
$cs_color['title']            = '#aec8e2';
$cs_color['links2sections']   = '#dfe9f3';
$cs_color['table_body']       = '#eeeeee';
$cs_color['table_head']       = '#dfe9f3';
$cs_color['room_title']       = $cs_color['title'];
$cs_color['room_background']  = $cs_color['background'];

// default color settings
$cs_color['DEFAULT']['schema']                     = 'DEFAULT';
$cs_color['DEFAULT']['tabs_background']            = '#3B658E';
$cs_color['DEFAULT']['tabs_focus']                 = '#EC930D';
$cs_color['DEFAULT']['table_background']           = '#EFEFEF';
$cs_color['DEFAULT']['tabs_title']                 = 'white';
$cs_color['DEFAULT']['headline_text']              = 'white';
$cs_color['DEFAULT']['hyperlink']                  = '#01458A';
$cs_color['DEFAULT']['help_background']            = '#2079D3';
$cs_color['DEFAULT']['boxes_background']           = 'white';
$cs_color['DEFAULT']['content_background']         = '#EFECE2';
$cs_color['DEFAULT']['list_entry_odd']             = '#EFECE2';
$cs_color['DEFAULT']['list_entry_even']            = '#F7F7F7';
$cs_color['DEFAULT']['myarea_headline_background'] = '#CDCBC2';
$cs_color['DEFAULT']['myarea_headline_title']      = 'white';
$cs_color['DEFAULT']['myarea_title_backround']     = '#F7F7F7';
$cs_color['DEFAULT']['myarea_content_backround']   = '#EFECE2';
$cs_color['DEFAULT']['myarea_section_title']       = '#666666';
$cs_color['DEFAULT']['portal_tabs_background']     = '#666666';
$cs_color['DEFAULT']['portal_tabs_title']          = 'white';
$cs_color['DEFAULT']['portal_tabs_focus']          = '#EC930D';
$cs_color['DEFAULT']['portal_td_head_background']  = '#F7F7F7';
$cs_color['DEFAULT']['index_td_head_title']        = 'white';
$cs_color['DEFAULT']['date_title']                 = '#EC930D';
$cs_color['DEFAULT']['info_color']                 = '#827F76';
$cs_color['DEFAULT']['disabled']                   = '#B0B0B0';
$cs_color['DEFAULT']['warning']                    = '#FC1D12';
$cs_color['DEFAULT']['head_background']            = '#2A4E72';




//Traum in Pink
$cs_color['SCHEMA_1']['schema']                     = 'SCHEMA_1';
$cs_color['SCHEMA_1']['tabs_background']            = '#C666D1';
$cs_color['SCHEMA_1']['tabs_focus']                 = '#680D74';
$cs_color['SCHEMA_1']['table_background']           = '#FDE5E5';
$cs_color['SCHEMA_1']['tabs_title']                 = 'white';
$cs_color['SCHEMA_1']['headline_text']              = 'white';
$cs_color['SCHEMA_1']['hyperlink']                  = '#00552B';
$cs_color['SCHEMA_1']['help_background']            = '#2079D3';
$cs_color['SCHEMA_1']['boxes_background']           = '#F7EDF0';
$cs_color['SCHEMA_1']['content_background']         = '#F2AFE4';
$cs_color['SCHEMA_1']['list_entry_odd']             = '#D9D9D9';
$cs_color['SCHEMA_1']['list_entry_even']            = '#F2AFE4';
$cs_color['SCHEMA_1']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_1']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_1']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_1']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_1']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_1']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_1']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_1']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_1']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_1']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_1']['date_title']                 = '#651063';
$cs_color['SCHEMA_1']['info_color']                 = '#6C6960';
$cs_color['SCHEMA_1']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_1']['warning']                    = 'red';

//Ozean
$cs_color['SCHEMA_2']['schema']                     = 'SCHEMA_2';
$cs_color['SCHEMA_2']['tabs_background']            = '#084296';
$cs_color['SCHEMA_2']['tabs_focus']                 = '#C62101';
$cs_color['SCHEMA_2']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_2']['tabs_title']                 = 'white';
$cs_color['SCHEMA_2']['headline_text']              = 'white';
$cs_color['SCHEMA_2']['hyperlink']                  = '#003366';
$cs_color['SCHEMA_2']['help_background']            = '#2079D3';
$cs_color['SCHEMA_2']['boxes_background']           = '#F5F5F5';
$cs_color['SCHEMA_2']['content_background']         = '#88BFB9';
$cs_color['SCHEMA_2']['list_entry_odd']             = '#F5F5F5';
$cs_color['SCHEMA_2']['list_entry_even']            = '#88BFB9';
$cs_color['SCHEMA_2']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_2']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_2']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_2']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_2']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_2']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_2']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_2']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_2']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_2']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_2']['date_title']                 = '#1B685B';
$cs_color['SCHEMA_2']['info_color']                 = '#646159';
$cs_color['SCHEMA_2']['disabled']                   = '#646159';
$cs_color['SCHEMA_2']['warning']                    = 'red';

//70er Jahre Wohnsimmer
$cs_color['SCHEMA_3']['schema']                     = 'SCHEMA_3';
$cs_color['SCHEMA_3']['tabs_background']            = '#963812';
$cs_color['SCHEMA_3']['tabs_focus']                 = '#BF9936';
$cs_color['SCHEMA_3']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_3']['tabs_title']                 = 'white';
$cs_color['SCHEMA_3']['headline_text']              = 'white';
$cs_color['SCHEMA_3']['hyperlink']                  = '#963812';
$cs_color['SCHEMA_3']['help_background']            = '#2079D3';
$cs_color['SCHEMA_3']['boxes_background']           = 'white';
$cs_color['SCHEMA_3']['content_background']         = '#E5D8AE';
$cs_color['SCHEMA_3']['list_entry_odd']             = '#F5F5F5';
$cs_color['SCHEMA_3']['list_entry_even']            = '#E5D8AE';
$cs_color['SCHEMA_3']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_3']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_3']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_3']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_3']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_3']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_3']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_3']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_3']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_3']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_3']['date_title']                 = '#BF9936';
$cs_color['SCHEMA_3']['info_color']                 = '#827F76';
$cs_color['SCHEMA_3']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_3']['warning']                    = 'red';

//Black and White
$cs_color['SCHEMA_4']['schema']                     = 'SCHEMA_4';
$cs_color['SCHEMA_4']['tabs_background']            = '#000000';
$cs_color['SCHEMA_4']['tabs_focus']                 = '#D4D0C7';
$cs_color['SCHEMA_4']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_4']['tabs_title']                 = 'white';
$cs_color['SCHEMA_4']['headline_text']              = 'white';
$cs_color['SCHEMA_4']['hyperlink']                  = '#525252';
$cs_color['SCHEMA_4']['help_background']            = '#2079D3';
$cs_color['SCHEMA_4']['boxes_background']           = '#F5F1E7';
$cs_color['SCHEMA_4']['content_background']         = 'white';
$cs_color['SCHEMA_4']['list_entry_odd']             = '#D4D0C7';
$cs_color['SCHEMA_4']['list_entry_even']            = 'white';
$cs_color['SCHEMA_4']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_4']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_4']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_4']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_4']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_4']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_4']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_4']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_4']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_4']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_4']['date_title']                 = '#000000';
$cs_color['SCHEMA_4']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_4']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_4']['warning']                    = 'red';

//Alles im grünen Bereich
$cs_color['SCHEMA_5']['schema']                     = 'SCHEMA_5';
$cs_color['SCHEMA_5']['tabs_background']            = '#4B6F85';
$cs_color['SCHEMA_5']['tabs_focus']                 = '#80CB8F';
$cs_color['SCHEMA_5']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_5']['tabs_title']                 = 'white';
$cs_color['SCHEMA_5']['headline_text']              = 'white';
$cs_color['SCHEMA_5']['hyperlink']                  = '#56695A';
$cs_color['SCHEMA_5']['help_background']            = '#2079D3';
$cs_color['SCHEMA_5']['boxes_background']           = '#E1F0E0';
$cs_color['SCHEMA_5']['content_background']         = '#BDDFC2';
$cs_color['SCHEMA_5']['list_entry_odd']             = '#F5F5F5';
$cs_color['SCHEMA_5']['list_entry_even']            = '#BDDFC2';
$cs_color['SCHEMA_5']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_5']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_5']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_5']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_5']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_5']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_5']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_5']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_5']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_5']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_5']['date_title']                 = '#4B6E84';
$cs_color['SCHEMA_5']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_5']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_5']['warning']                    = 'red';

//Wie ein sonniger Tag
$cs_color['SCHEMA_6']['schema']                     = 'SCHEMA_6';
$cs_color['SCHEMA_6']['tabs_background']            = '#F7B500';
$cs_color['SCHEMA_6']['tabs_focus']                 = '#F29305';
$cs_color['SCHEMA_6']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_6']['tabs_title']                 = 'white';
$cs_color['SCHEMA_6']['headline_text']              = 'white';
$cs_color['SCHEMA_6']['hyperlink']                  = '#8C2224';
$cs_color['SCHEMA_6']['help_background']            = '#2079D3';
$cs_color['SCHEMA_6']['boxes_background']           = '#FEFDDB';
$cs_color['SCHEMA_6']['content_background']         = '#F9F8CA';
$cs_color['SCHEMA_6']['list_entry_odd']             = 'white';
$cs_color['SCHEMA_6']['list_entry_even']            = '#F9F8CA';
$cs_color['SCHEMA_6']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_6']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_6']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_6']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_6']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_6']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_6']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_6']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_6']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_6']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_6']['date_title']                 = '#F29305';
$cs_color['SCHEMA_6']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_6']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_6']['warning']                    = 'red';

//Himmelblau
$cs_color['SCHEMA_7']['schema']                     = 'SCHEMA_7';
$cs_color['SCHEMA_7']['tabs_background']            = '#ADCBE7';
$cs_color['SCHEMA_7']['tabs_focus']                 = '#A8B4CC';
$cs_color['SCHEMA_7']['table_background']           = '#EFEFEF';
$cs_color['SCHEMA_7']['tabs_title']                 = 'white';
$cs_color['SCHEMA_7']['headline_text']              = 'white';
$cs_color['SCHEMA_7']['hyperlink']                  = '#003366';
$cs_color['SCHEMA_7']['help_background']            = '#2079D3';
$cs_color['SCHEMA_7']['boxes_background']           = 'white';
$cs_color['SCHEMA_7']['content_background']         = '#F6F2EF';
$cs_color['SCHEMA_7']['list_entry_odd']             = '#DDE9F5';
$cs_color['SCHEMA_7']['list_entry_even']            = '#F6F2EF';
$cs_color['SCHEMA_7']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_7']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_7']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_7']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_7']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_7']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_7']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_7']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_7']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_7']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_7']['date_title']                 = '#A8B4CC';
$cs_color['SCHEMA_7']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_7']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_7']['warning']                    = 'red';

//Rot
$cs_color['SCHEMA_8']['schema']                     = 'SCHEMA_8';
$cs_color['SCHEMA_8']['tabs_background']            = '#A50416';
$cs_color['SCHEMA_8']['tabs_focus']                 = '#F6B700';
$cs_color['SCHEMA_8']['table_background']           = '#A50416';
$cs_color['SCHEMA_8']['tabs_title']                 = 'white';
$cs_color['SCHEMA_8']['headline_text']              = 'white';
$cs_color['SCHEMA_8']['hyperlink']                  = '#69151A';
$cs_color['SCHEMA_8']['help_background']            = '#2079D3';
$cs_color['SCHEMA_8']['boxes_background']           = 'white';
$cs_color['SCHEMA_8']['content_background']         = '#CCB29C';
$cs_color['SCHEMA_8']['list_entry_odd']             = '#FFFEFF';
$cs_color['SCHEMA_8']['list_entry_even']            = '#EAE3EB';
$cs_color['SCHEMA_8']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_8']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_8']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_8']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_8']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_8']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_8']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_8']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_8']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_8']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_8']['date_title']                 = '#A50416';
$cs_color['SCHEMA_8']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_8']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_8']['warning']                    = 'red';
$cs_color['SCHEMA_8']['repeat_background']         = 'x';

//Fade to Grey
$cs_color['SCHEMA_9']['schema']                     = 'SCHEMA_9';
$cs_color['SCHEMA_9']['tabs_background']            = '#8D8C8C';
$cs_color['SCHEMA_9']['tabs_focus']                 = '#c17f2a';
$cs_color['SCHEMA_9']['table_background']           = '#A50416';
$cs_color['SCHEMA_9']['tabs_title']                 = 'white';
$cs_color['SCHEMA_9']['headline_text']              = 'white';
$cs_color['SCHEMA_9']['hyperlink']                  = '#990000';
$cs_color['SCHEMA_9']['help_background']            = '#2079D3';
$cs_color['SCHEMA_9']['boxes_background']           = '#F5F5F5';
$cs_color['SCHEMA_9']['content_background']         = '#FAFAFA';
$cs_color['SCHEMA_9']['list_entry_odd']             = '#FFFFFF';
$cs_color['SCHEMA_9']['list_entry_even']            = '#EEEEEE';
$cs_color['SCHEMA_9']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_9']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_9']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_9']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_9']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_9']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_9']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_9']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_9']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_9']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_9']['date_title']                 = '#AD6B20';
$cs_color['SCHEMA_9']['info_color']                 = '#777777';
$cs_color['SCHEMA_9']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_9']['warning']                    = '#FF0000';

//5ter Stock
$cs_color['SCHEMA_10']['schema']                     = 'SCHEMA_10';
$cs_color['SCHEMA_10']['tabs_background']            = '#b42525';
$cs_color['SCHEMA_10']['tabs_focus']                 = '#bfbfbf';
$cs_color['SCHEMA_10']['table_background']           = '#b42525';
$cs_color['SCHEMA_10']['tabs_title']                 = 'white';
$cs_color['SCHEMA_10']['headline_text']              = 'white';
$cs_color['SCHEMA_10']['hyperlink']                  = '#990000';
$cs_color['SCHEMA_10']['help_background']            = '#2079D3';
$cs_color['SCHEMA_10']['boxes_background']           = 'white';
$cs_color['SCHEMA_10']['content_background']         = '#ffffff';
$cs_color['SCHEMA_10']['list_entry_odd']             = '#F7F7F7';
$cs_color['SCHEMA_10']['list_entry_even']            = '#bfbfbf';
$cs_color['SCHEMA_10']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_10']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_10']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_10']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_10']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_10']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_10']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_10']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_10']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_10']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_10']['date_title']                 = '#b42525';
$cs_color['SCHEMA_10']['info_color']                 = '#827f76';
$cs_color['SCHEMA_10']['disabled']                   = '#bfbfbf';
$cs_color['SCHEMA_10']['warning']                    = '#FF0000';

//Fleder ist Trumpf
$cs_color['SCHEMA_11']['schema']                     = 'SCHEMA_11';
$cs_color['SCHEMA_11']['tabs_background']            = '#555A92';
$cs_color['SCHEMA_11']['tabs_focus']                 = '#BFBFD8';
$cs_color['SCHEMA_11']['table_background']           = '#555A92';
$cs_color['SCHEMA_11']['tabs_title']                 = 'white';
$cs_color['SCHEMA_11']['headline_text']              = 'white';
$cs_color['SCHEMA_11']['hyperlink']                  = '#555A92';
$cs_color['SCHEMA_11']['help_background']            = '#2079D3';
$cs_color['SCHEMA_11']['boxes_background']           = '#F5F5F5';
$cs_color['SCHEMA_11']['content_background']         = '#F7F7F7';
$cs_color['SCHEMA_11']['list_entry_odd']             = '#F5F5F5';
$cs_color['SCHEMA_11']['list_entry_even']            = '#BFBFD8';
$cs_color['SCHEMA_11']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_11']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_11']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_11']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_11']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_11']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_11']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_11']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_11']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_11']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_11']['date_title']                 = '#666699';
$cs_color['SCHEMA_11']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_11']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_11']['warning']                    = 'red';



//SCHEMA 12
/*****************************************************************/
$cs_color['SCHEMA_12']['schema']                     = 'SCHEMA_12';
$cs_color['SCHEMA_12']['tabs_background']            = '#36D636';
$cs_color['SCHEMA_12']['tabs_focus']                 = '#CCFF99';
$cs_color['SCHEMA_12']['tabs_title']                 = '#1A4120';
$cs_color['SCHEMA_12']['content_background']         = '#9AFE66';
$cs_color['SCHEMA_12']['boxes_background']           = '#CCFF99';
$cs_color['SCHEMA_12']['hyperlink']                  = '#625252';
$cs_color['SCHEMA_12']['list_entry_even']            = '#CCFF99';

$cs_color['SCHEMA_12']['table_background']           = $cs_color['SCHEMA_12']['content_background'];
$cs_color['SCHEMA_12']['headline_text']              = $cs_color['SCHEMA_12']['tabs_title'];
$cs_color['SCHEMA_12']['help_background']            = $cs_color['SCHEMA_12']['content_background'];
$cs_color['SCHEMA_12']['list_entry_odd']             = '#FFFFFF';

$cs_color['SCHEMA_12']['date_title']                 = '#666699';
$cs_color['SCHEMA_12']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_12']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_12']['warning']                    = 'red';

$cs_color['SCHEMA_12']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_12']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_12']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_12']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_12']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_12']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_12']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_12']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_12']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_12']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_12']['repeat_background']          = 'xy';
/*****************************************************************/

//SCHEMA 13
/*****************************************************************/
$cs_color['SCHEMA_13']['schema']                     = 'SCHEMA_13';
$cs_color['SCHEMA_13']['tabs_background']            = '#FFCC66';
$cs_color['SCHEMA_13']['tabs_focus']                 = '#ECB851';
$cs_color['SCHEMA_13']['tabs_title']                 = '#4D7104';
$cs_color['SCHEMA_13']['content_background']         = '#FEFFD5';
$cs_color['SCHEMA_13']['boxes_background']           = '#FFFF99';
$cs_color['SCHEMA_13']['hyperlink']                  = '#625252';
$cs_color['SCHEMA_13']['list_entry_even']            = '#FFFF99';

$cs_color['SCHEMA_13']['table_background']           = $cs_color['SCHEMA_13']['content_background'];
$cs_color['SCHEMA_13']['headline_text']              = $cs_color['SCHEMA_13']['tabs_title'];
$cs_color['SCHEMA_13']['help_background']            = $cs_color['SCHEMA_13']['content_background'];
$cs_color['SCHEMA_13']['list_entry_odd']             = '#FFFFFF';

$cs_color['SCHEMA_13']['date_title']                 = '#666699';
$cs_color['SCHEMA_13']['info_color']                 = '#7E7D7B';
$cs_color['SCHEMA_13']['disabled']                   = '#B0B0B0';
$cs_color['SCHEMA_13']['warning']                    = 'red';

$cs_color['SCHEMA_13']['myarea_headline_background'] = '#CDCBC2';
$cs_color['SCHEMA_13']['myarea_headline_title']      = 'white';
$cs_color['SCHEMA_13']['myarea_title_backround']     = '#F7F7F7';
$cs_color['SCHEMA_13']['myarea_content_backround']   = '#EFECE2';
$cs_color['SCHEMA_13']['myarea_section_title']       = '#666666';
$cs_color['SCHEMA_13']['portal_tabs_background']     = 'blue';
$cs_color['SCHEMA_13']['portal_tabs_title']          = 'white';
$cs_color['SCHEMA_13']['portal_tabs_focus']          = '#EC930D';
$cs_color['SCHEMA_13']['portal_td_head_background']  = '#F7F7F7';
$cs_color['SCHEMA_13']['index_td_head_title']        = 'white';
$cs_color['SCHEMA_13']['repeat_background']          = 'xy';
/*****************************************************************/


// color settings for print view
$cs_color['print_background']       = '#ffffff';
$cs_color['print_text']             = '#000000';
$cs_color['print_static_text']      = '#000000';
$cs_color['print_hyperlink']        = '#000000';
$cs_color['print_link_disabled']    = '#000000';
$cs_color['print_frozen']           = '#000000';
$cs_color['print_title']            = '#000000';
$cs_color['print_links2sections']   = '#c2c2c2';
$cs_color['print_table_body']       = '#ffffff';
$cs_color['print_table_head']       = '#c2c2c2';
$cs_color['print_room_title']       = $cs_color['print_title'];
$cs_color['print_room_background']  = $cs_color['print_background'];



define('LF'  , "\n");       // line feed
define('BR'  , "<br />");   // line feed
define('BRLF', "<br />\n"); // line feed
define('TAB' , "\t");       // tab

define ("UC_CHARS", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖŒØŠÙÚÛÜÝŽÞ"); // If you need more, add
define ("LC_CHARS", "àáâãäåæçèéêëìíîïðñòóôõöœøšùúûüýžþ"); // If you need more, add

//All Special chars
define ("SPECIAL_CHARS",UC_CHARS.LC_CHARS."ß");

//Chars allowed  in URLs by RFC 1738. Use in character class definition: '['.RFC1738_CHARS.']'
//IMPORTANT: for preg-functions: use "§" as delimiters for search-expression!
//
// the ")" character belongs to the allowed cahracters too, but there are problems with things like "(www.test.de)." where both ")" and "." are punctuation marks,
//but both could be also parts of the url. For the few occurances of ")" in urls, we decided to solve this problem by not allowing ")" in urls
define ("RFC1738_CHARS","A-Za-z0-9?:@&=/;_.+!*'(,%$~#-");

//Chars allowed  in Email-adressess by RFC 2822. Use in character class definition: '['.RFC2822_CHARS.']'
//IMPORTANT: for preg-functions: use "§" as delimiters for search-expression!
define("RFC2822_CHARS","A-Za-z0-9!#$%&'*+/=?^_`{|}~-");

// IIS - microsoft compatibility
/*
if ( !isset($_SERVER['HTTP_REFERER']) ) {
   if ( isset($_SERVER['SERVER_PORT'])
        and !empty($_SERVER['SERVER_PORT'])
        and $_SERVER['SERVER_PORT'] == 443
      ) {
      $retour = 'https://';
   } else {
      $retour = 'http://';
   }
   if ( isset($_SERVER['HTTP_HOST'])
        and !empty($_SERVER['HTTP_HOST'])
      ) {
      $retour .= $_SERVER['HTTP_HOST'];
   }
   if ( isset($_SERVER['PHP_SELF'])
        and !empty($_SERVER['PHP_SELF'])
      ) {
      $pos = strrpos($_SERVER['PHP_SELF'],'/');
      $path = substr($_SERVER['PHP_SELF'],0,$pos);
      $retour .= $path;
   }
   $_SERVER['HTTP_REFERER'] = $retour;
}
*/
?>