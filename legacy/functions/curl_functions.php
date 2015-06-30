<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, JosÃ¯Â¿Â½ Manuel GonzÃ¯Â¿Â½lez VÃ¯Â¿Â½zquez
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

function curl_mailto( $mailaddress, $linktext, $title="" ) {
   if(isset($_GET['mode']) and $_GET['mode']=='print'){
      return $linktext;
   } else {
      return "<a href=\"mailto:".$mailaddress."\" title=\"".$title."\">".$linktext."</a>";
   }
}

/**
 * Construct a hypertext reference in conjunction with a curl.
 * This function is for convenience and combines curl construction
 * with ahref construction.
 *
 * @param   $module     commsy module referring to
 * @param   $function   functions are represented by filesnames (without extension)
 * @param   $parameter  normal parameters ARRAY
 * @param   $linktext   the link text
 * @param   $title      (optional) title attribute
 * @param   $target     (optional) target attribute
 * @param   $fragment   (optional) anchor what goes behind a '#'
 * @param   $filehack   (optional)
 * @param   $jshack     (optional)
 * @param   $name       (optional) anchor: name of link (refered to by $fragment)
 * @param   $style      (optional) css style
 * @param   $file       (optional) for switching between commsy tools
 *
 * @return  returns the constructed ahref-tag as a string
 */
function ahref_curl( $context_id, $module, $function, $parameter, $linktext, $title='', $target='', $fragment = '', $filehack='', $jshack='',$name='', $style='', $file='', $id='',$empty_adress = false ) {
   $address = curl($context_id, $module, $function, $parameter, $fragment, $filehack, $file );
   if ( !empty($style) ) {
      $style = ' '.$style;
   }
   if (!$empty_adress){
       #$ahref = '<a'.$style.' href="'.$address.'"';
       $ahref = '<a href="'.$address.'"'.$style;
       if ( $title != '' )  $ahref .= ' title="'.strip_tags($title).'"';
       if ( $name != '' )   $ahref .= ' name="'.$name.'"';
       if ( $target != '' ) $ahref .= ' target="'.$target.'"';
       if ( $jshack != '' ) $ahref .= ' '.$jshack;
       if ( $id != '' )     $ahref .= ' id="'.$id.'"';


       $ahref .= '>'.$linktext.'</a>';
       if ( isset($_GET['mode'])
            and $_GET['mode'] == 'print'
            and ( empty($_GET['download'])
                  or $_GET['download'] != 'zip'
                )
          ) {
          return $linktext;
       }else{
          return $ahref;
       }
   }else{
       $ahref = '<a'.$style;
       if ( $title != '' )  $ahref .= ' title="'.strip_tags($title).'"';
       if ( $name != '' )   $ahref .= ' name="'.$name.'"';
       if ( $target != '' ) $ahref .= ' target="'.$target.'"';
       if ( $jshack != '' ) $ahref .= ' '.$jshack;
       if ( $id != '' )     $ahref .= ' id="'.$id.'"';


       $ahref .= '>'.$linktext.'</a>';
       if(isset($_GET['mode']) and $_GET['mode']=='print'){
          return $linktext;
       }else{
          return $ahref;
       }
   }
}

function ahref_curl2 ( $curl, $linktext, $title='', $target='', $fragment = '', $jshack='',$name='', $style='', $id='', $empty_adress = false ) {
   if ( !empty($style) ) {
      $style = ' '.$style;
   }
   $retour = '<a'.$style.' href="'.$curl.'"';
   if ( $title != '' )  $retour .= ' title="'.strip_tags($title).'"';
   if ( $name != '' )   $retour .= ' name="'.$name.'"';
   if ( $target != '' ) $retour .= ' target="'.$target.'"';
   if ( $jshack != '' ) $retour .= ' '.$jshack;
   if ( $id != '' )     $retour .= ' id="'.$id.'"';

   $retour .= '>'.$linktext.'</a>';
   return $retour;
}

/**
 * Construct a commsy url (custom url) related to a given schema.
 * Module and function specify a target script to be called with this curl,
 * parameter and fragment are used to pass values to that script,
 * filehack is used to fake a files realname into a curl (used by
 * the material manager).
 *
 * @param   $module     commsy module referring to
 * @param   $function   functions are represented by filesnames (without extension)
 * @param   $parameter  normal parameters ARRAY
 * @param   $fragment   (optional) anchor what goes behind a '#'
 * @param   $filehack   (optional) for faking real filenames into file downloads
 * @param   $file       (optional) for switching between commsy tools
 *
 */
function curl( $context_id, $module, $function, $parameter, $fragment='', $filehack='', $file='' ) {
   return _curl(true,$context_id,$module,$function,$parameter,$fragment,$filehack,$file);
}

/**
 * Construct a commsy url (custom url) related to a given schema.
 * Module and function specify a target script to be called with this curl,
 * parameter and fragment are used to pass values to that script,
 * filehack is used to fake a files realname into a curl (used by
 * the material manager).
 *
 * @param   $amp_flag   true = '&amp;' for HTML - false = '&' for redirects
 * @param   $context_id id of the current context
 * @param   $module     commsy module referring to
 * @param   $function   functions are represented by filesnames (without extension)
 * @param   $parameter  array normal parameters
 * @param   $fragment   (optional) anchor what goes behind a '#'
 * @param   $filehack   (optional) for faking real filenames into file downloads
 * @param   $file       (optional) for switching between commsy tools
 *
 */
function _curl( $amp_flag, $context_id, $module, $function, $parameter, $fragment='', $filehack='', $file='' ) {
    global $environment;
   
   if ( empty($file) ) {
      $address = mb_substr($_SERVER['SCRIPT_NAME'],mb_strrpos($_SERVER['SCRIPT_NAME'],'/')+1);
      global $c_single_entry_point;
      if ( $address != $c_single_entry_point) {
         $address = $c_single_entry_point;
      }
   } else {
      $address = $file;
      if ( !strstr($file,'.php') ) {
         $address .= '.php';
      }
   }
   if (!empty($amp_flag) and $amp_flag) {
      $amp_flag = '&amp;';
   } else {
      $amp_flag = '&';
     // cause this are redirects and not links
   }

   if ( !empty($parameter) and is_array($parameter) ) {
      $param_string = '';
      foreach ($parameter as $key => $value) {
      	#if ( !stristr($value,'%') ) {
      	#	$value = rawurlencode($value);
      	#}
         $param_string .= $amp_flag.$key.'='.$value;
      }
      $parameter = $param_string;
      unset($param_string);
   } elseif (is_array($parameter)) {
      $parameter = '';
   } elseif ( !empty($parameter) ) {
      include_once('functions/error_functions.php');
      trigger_error('parameter must be given in an array NOT in a string',E_USER_WARNING);
   }

   if ( !empty($filehack) ) {
      $filehack = rawurlencode($filehack);
      $address .= '/'.$filehack;
   }

   $address .= '?cid='.$context_id;
   $address .= $amp_flag.'mod='.$module.$amp_flag.'fct='.$function;

   if ( !empty($parameter) ) {
      $address .= $parameter;
   }

   $session = $environment->getSessionItem();
   if ( !strstr($parameter,'SID') and !empty($session) ) {
      $current_SID = $session->getSessionID();
      if ((!$session->issetValue('cookie') or $session->getValue('cookie') == '0') and !empty($current_SID)) {
         $address .= $amp_flag.'SID='.$current_SID;
      }
      
      // multi master implementation (06.09.2021 IJ)
      $db = $environment->getConfiguration('db');
      if ( count($db) > 1 ) {
      	if ( !$session->issetValue('cookie')
      	     or $session->getValue('cookie') == '0'
      	     or empty($_COOKIE['db_pid'])
      	   ) {
      		$db_pid = $environment->getDBPortalID();
      		if ( $environment->inServer()
      		     or $environment->inPortal()
      		   ) {
      			$cs_pid = $environment->getCurrentContextID();
      		} else {
      			$cs_pid = $environment->getCurrentPortalID();
      		}
      		if ( empty($db_pid)
      		     or $db_pid != $cs_pid
      		   ) {
      			$db_pid = $cs_pid;
      		}
      		if ( !empty($db_pid) ) {
      			if ( !strstr($address,'db_pid='.$db_pid) ) {
      				if ( strstr($address,'db_pid=') ) {
      					$address = preg_replace('/'.$amp_flag.'db_pid=[0-9]*/', '', $address);
      				}
      				$address .= $amp_flag.'db_pid='.$db_pid;
      			}
      		}
      	} elseif ( !empty($_COOKIE['db_pid'])
      	           and strstr($address,'db_pid=')
      	         ) {
      		$address = preg_replace('/'.$amp_flag.'db_pid=[0-9]*/', '', $address);
      	} elseif ( !empty($_COOKIE['db_pid'])
      	           and !strstr($address,'db_pid=')
      	   ) {
      		$db_pid = $environment->getDBPortalID();
      		if ( $environment->inServer()
      		     or $environment->inPortal()
      		   ) {
      			$cs_pid = $environment->getCurrentContextID();
      		} else {
      			$cs_pid = $environment->getCurrentPortalID();
      		}
      		if ( empty($db_pid)
      		     or $db_pid != $cs_pid
      		   ) {
      			$address .= $amp_flag.'db_pid='.$cs_pid;
      		}
      	}
      }
      // multi master implementation - END
      
   }

   if ( !empty($fragment) ) {
      $address .= '#'.$fragment;
   }
   return $address;
}

/**
 * for java migration
 *
 * @param   $get   array with get parameter from http request
 * @param   $post  array with post parameter from http request
 */
function parameterString( $get,$post ) {
   $param = "";
   foreach ( $get as $key => $value) {
      if ( $key != 'mod' and $key != 'fct') {
        $param .= "&".$key."=".$value;
      }
   }
   foreach ( $post as $key => $value) {
      if ( $key != 'mod' and $key != 'fct') {
         $param .= "&".$key."=".$value;
     }
   }
   return $param;
}
?>