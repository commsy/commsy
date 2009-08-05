<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

class misc_text_converter {

   private $_environment = NULL;
   private $_div_number = NULL;

   public function __construct ($params) {
      if ( !empty($params['environment']) ) {
         $this->_environment = $params['environment'];
      } else {
         include_once('functions/error_functions.php');
         trigger_error('no environment defined '.__FILE__.' '.__LINE__,E_USER_ERROR);
      }
   }

   private function _getDivNumber() {
      if ( !isset($this->_div_number) ) {
         $this->_div_number = '1';
      } else {
         $this->_div_number++;
      }
      return $this->_div_number;
   }

   private function _parseArgs ($x) {
      $z = array();
      preg_match_all('~([-+]|(?>(\\w+)[:=]{0,1}))?("[^"]*"|\'[^\']*\'|\\S+)~u',$x, $terms, PREG_SET_ORDER);
      foreach($terms as $t) {
         $v = preg_replace('~^([\'"])?(.*)\\1$~u', '$2', $t[3]);
         if ($t[2]) { $z['#'][] = $t[2]; $z[$t[2]] = $v; }
         else { $z['#'][] = $t[1]; $z[$t[1]][] = $v; }
         $z['#'][] = $v;
      }
      return $this->_checkSecurity($z);
   }

   private function _checkSecurity ( $array ) {
      $retour = array();
      foreach ( $array as $key => $value ) {
         if ( $key == 'float'
              and $value != 'left'
              and $value != 'right'
            ) {
            #include_once('functions/error_functions.php');
            #trigger_error('float must be left or right',E_USER_WARNING);
         } elseif ( $key == 'text'
                    or $key == 'alt'
                    or $key == 'gallery'
                    or $key == 'image'
                    or $key == 'server'
                  ) {
            $retour[$key] = $this->_htmlentities_small($value);
         } elseif ( $key == 'width'
                    and !is_numeric($value)
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('width must be a number',E_USER_WARNING);
         } elseif ( $key == 'height'
                    and !is_numeric($value)
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('height must be a number',E_USER_WARNING);
         } elseif ( $key == 'width'
                    and $value > 1000
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('width must be under 1000',E_USER_WARNING);
         } elseif ( $key == 'height'
                    and $value > 1000
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('height must be under 1000',E_USER_WARNING);
         } elseif ( $key == 'icon'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('icon must be true or false',E_USER_WARNING);
         } elseif ( $key == 'size'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('size must be true or false',E_USER_WARNING);
         } elseif ( $key == 'play'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('play must be true or false',E_USER_WARNING);
         } elseif ( $key == 'navigation'
                    and $value != 'true'
                    and $value != 'false'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('navigation must be true or false',E_USER_WARNING);
         } elseif ( $key == 'orientation'
                    and $value != 'portrait'
                    and $value != 'landscape'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('orientation must be portrait or landscape',E_USER_WARNING);
         } elseif ( $key == 'target'
                    and $value != '_blank'
                    and $value != '_top'
                    and $value != '_parent'
                  ) {
            #include_once('functions/error_functions.php');
            #trigger_error('target must be _blank, _top or _parent',E_USER_WARNING);
         } else {
            $retour[$key] = $value;
         }
      }
      return $retour;
   }

   private function _htmlentities_small ( $value ) {
      $value = str_replace('<','&lt;',$value);
      $value = str_replace('>','&gt;',$value);
      $value = str_replace('"','&quot;',$value);
      $value = str_replace('\'','&quot;',$value);
      return $value;
   }

   /**
    * Extended implementation of the standard PHP-Function
    *
    * Needed to ensure proper searching in CommSy with standard PHP settings
    * When the 'locale' setting of PHP is not set properly, the search for language specific characters
    * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtolower doesn't translate
    * them (http://de3.php.net/manual/en/function.strtolower.php)
    *
    * Our extended implementation translates correct without respect to 'locale'
    */

   private function _cs_strtolower ($value) {
      return (mb_strtolower(strtr($value, UC_CHARS, LC_CHARS), 'UTF-8'));
   }

   public function formatFile ( $text, $array, $file_name_array ) {
      $retour = '';
      $image_text = '';
      if ( !empty($array[1])
           and !empty($file_name_array)
         ) {
         $temp_file_name = htmlentities($array[1], ENT_NOQUOTES, 'UTF-8');
         if ( !empty($file_name_array[$temp_file_name]) ) {
            $file = $file_name_array[$temp_file_name];
         } elseif ( !empty($file_name_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')]) ) {
            $file = $file_name_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')];
         }
         if ( isset($file) ) {

            if ( !empty($array[2]) ) {
               $args = $this->_parseArgs($array[2]);
            } else {
               $args = array();
            }

            if ( empty($args['icon'])
                 or ( !empty($args['icon'])
                      and $args['icon'] == 'true'
                    )
               ) {
               $icon = $file->getFileIcon().' ';
            } else {
               $icon = '';
            }
            if ( empty($args['size'])
                 or ( !empty($args['size'])
                      and $args['size'] == 'true'
                    )
               ) {
               $kb = ' ('.$file->getFileSize().' KB)';
            } else {
               $kb = '';
            }
            if ( !empty($args['text']) ) {
               $name = $args['text'];
            } else {
               $name = $file->getDisplayName();
            }

            if ( !empty($args['target']) ) {
               $target = ' target="'.$args['target'].'"';
            } elseif ( !empty($args['newwin']) ) {
               $target = ' target=_blank;';
            } else {
               $target = '';
            }
            $source = $file->getUrl();
            $image_text = '<a href="'.$source.'"'.$target.'>'.$icon.$name.'</a>'.$kb;
         }
      }

      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   public function formatFlash ( $text, $array, $file_name_array ) {
      $retour = '';
      if ( empty($array[1]) ) {
         // internal resource
         if ( !empty($file_name_array) ) {
            $file = $file_name_array[$array[2]];
            if ( isset($file) ) {
               $source = $file->getURL();
               $ext = $file->getExtension();
               $extern = false;
            }
         }
      } else {
         $source = $array[1].$array[2];
         $ext = $this->_cs_strtolower(mb_substr(strrchr($source,'.'),1));
         $extern = true;
      }
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( !empty($args['navigation'])
           and $args['navigation']
         ) {
         $with_player = true;
      } elseif ( !empty($args['navigation'])
                 and !$args['navigation']
               ) {
         $with_player = false;
      } elseif ( $ext == 'swf' ) {
         $with_player = false;
      } else {
         $with_player = true;
      }

      if ( !empty($source) ) {
         $image_text = '';
         if ( $ext == 'swf'
              and !$with_player
            ) {
            $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
            $image_text .= '   <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'.LF;
            if ( !empty($args['width']) ) {
               $image_text .= '           width="'.$args['width'].'"'.LF;
            }
            if ( !empty($args['height']) ) {
               $image_text .= '           height="'.$args['height'].'"'.LF;
            }
            $image_text .= '           codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0">'.LF;
            $image_text .= '      <param name="movie" value="'.$source.'" />'.LF;
            $image_text .= '      <param name="quality" value="high" />'.LF;
            $image_text .= '      <param name="scale" value="exactfit" />'.LF;
            $image_text .= '      <param name="menu" value="true" />'.LF;
            $image_text .= '      <param name="bgcolor" value="#000000" />'.LF;
            $image_text .= '      <param name="wmode" value="opaque" />'.LF;
            $image_text .= '      <param name="play" value="'.$play.'" />'.LF;
            $image_text .= '      <param name="loop" value="false" />'.LF;
            $image_text .= '      <param name="devicefont" value="true" />'.LF;
            $image_text .= '      <embed src="'.$source.'" quality="high"'.LF;
            $image_text .= '             scale="exactfit"'.LF;
            $image_text .= '             menu="true"'.LF;
            $image_text .= '             bgcolor="#000000"'.LF;
            $image_text .= '             wmode="opaque"'.LF;
            $image_text .= '             play="'.$play.'"'.LF;
            $image_text .= '             loop="false"'.LF;
            $image_text .= '             devicefont="true"'.LF;
            if ( !empty($args['width']) ) {
               $image_text .= '             width="'.$args['width'].'"'.LF;
            }
            if ( !empty($args['height']) ) {
               $image_text .= '             height="'.$args['height'].'"'.LF;
            }
            $image_text .= '             swLiveConnect="false"'.LF;
            $image_text .= '             type="application/x-shockwave-flash"'.LF;
            $image_text .= '             pluginspage="http://www.macromedia.com/go/getflashplayer">'.LF;
            $image_text .= '      </embed>'.LF;
            $image_text .= '   </object>'.LF;
            $image_text .= '</div>'.LF;
         }

         else {
            // use flv media player for swf, flv
            // see: http://www.jeroenwijering.com/?item=JW_FLV_Media_Player
            if ( empty($args['width']) ) {
               $current_item = $this->_environment->getCurrentContextItem();
               if ( $current_item->isDesign7() ) {
                  $args['width'] = '600'; // 16:9
               } else {
                  $args['width'] = '300'; // old
               }
               unset($current_item);
            }
            if ( empty($args['height']) ) {
               $current_item = $this->_environment->getCurrentContextItem();
               if ( $current_item->isDesign7() ) {
                  $args['height'] = '337.5';  // 16:9
               } else {
                  $args['height'] = '250'; // old
               }
            }
            if ( $this->_environment->getCurrentBrowser() == 'MSIE' ) {
               $args['height'] -= 10;
               if ( $args['height'] > 0 ) {
                  $args['width'] -= round(($args['width']*10/$args['height']),0);
               } else {
                  $args['width'] = 0;
               }
            }
            $source = str_replace('&amp;','&',$source);
            $source = urlencode($source);
            if ($this->_environment->getCurrentBrowser() == 'MSIE' ) {
               $image_text .= '<div style="'.$float.' padding:10px;">'.LF;
               $image_text .= '<embed'.LF;
               $image_text .= '  src="mediaplayer.swf"'.LF;
               $image_text .= '  width="'.$args['width'].'"'.LF;
               $image_text .= '  height="'.$args['height'].'"'.LF;
               $image_text .= '  allowfullscreen="true"'.LF;
               $image_text .= '  wmode="opaque"'.LF;
               $image_text .= '  flashvars="file='.$source.'&autostart='.$play.'&type='.$ext;
               if ( !$extern ) {
                  $image_text .= '&showdigits=true';
               } else {
                  $image_text .= '&showdigits=false';
                  $image_text .= '&showicons=false';
               }
               if ( !$with_player ) {
                  $image_text .= '&shownavigation=false';
               }
               $image_text .= '" />'.LF;
               $image_text .= '</div>'.LF;
            } else {
               $div_number = $this->_getDivNumber();
               $image_text .= '<div id="id'.$div_number.'" style="'.$float.' padding:10px;">'.getMessage('COMMON_GET_FLASH').'</div>'.LF;
               $image_text .= '<script type="text/javascript">'.LF;
               $image_text .= '  var so = new SWFObject(\'mediaplayer.swf\',\'mpl\',\''.$args['width'].'\',\''.$args['height'].'\',\'8\');'.LF;
               $image_text .= '  so.addParam(\'allowfullscreen\',\'true\');'.LF;
               $image_text .= '  so.addParam(\'wmode\',\'opaque\');'.LF;
               $image_text .= '  so.addVariable(\'file\',"'.$source.'");'.LF;
               $image_text .= '  so.addVariable(\'autostart\',\''.$play.'\');'.LF;
               $image_text .= '  so.addVariable(\'overstretch\',\'fit\');'.LF;
               $image_text .= '  so.addVariable(\'showstop\',\'true\');'.LF;
               if ( !empty($ext) ) {
                  $image_text .= '  so.addVariable(\'type\',\''.$ext.'\');'.LF;
               }
               if ( !$extern ) {
                  $image_text .= '  so.addVariable(\'showdigits\',\'true\');'.LF;
               } else {
                  $image_text .= '  so.addVariable(\'showdigits\',\'false\');'.LF;
                  $image_text .= '  so.addVariable(\'showicons\',\'false\');'.LF;
               }
               if ( !$with_player ) {
                  $image_text .= '  so.addVariable(\'shownavigation\',\'false\');'.LF;
               }
               $image_text .= '  so.write(\'id'.$div_number.'\');'.LF;
               $image_text .= '</script>'.LF;
            }
         }
         $text = str_replace($array[0],$image_text,$text);
      }
      $retour = $text;
      return $retour;
   }
}
?>