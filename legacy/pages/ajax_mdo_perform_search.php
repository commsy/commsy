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

include_once('functions/development_functions.php');
require_once('functions/security_functions');

$access = false;

// check for rights for mdo
$current_context_item = $environment->getCurrentContextItem();
if($current_context_item->isProjectRoom()) {
  // does this project room has any community room?
  $community_list = $current_context_item->getCommunityList();
  if($community_list->isNotEmpty()) {
    // check for community rooms activated the mdo feature
    $community = $community_list->getFirst();
    while($community) {
      $mdo_active = $community->getMDOActive();
      if(!empty($mdo_active) && $mdo_active != '-1') {
        // mdo access granted, get content from Mediendistribution-Online
        $access = true;
        
        // stop searching here
        break;
      }
      
      $community = $community_list->getNext();
    }
  }
}

if($access === true) {
  $curl_handler = curl_init('http://arix.datenbank-bildungsmedien.net/HH');
  curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl_handler, CURLOPT_POST, true);
  
  ############################
  ## 1. CommSy -> Arix: <notch type='commsy' />
  ## 2. Arix -> CommSy: <notch id='SESSION_ID'>NOTCH</notch> 
  ############################
  $data = '<notch type="commsy" />';
  curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array('xmlstatement' => $data));
  $response = curl_exec($curl_handler);
  if(!$response) {
    $page->add('success', 'false');
  } else {
    $xml_object = simplexml_load_string($response);
    $result = $xml_object->xpath('/notch[@id]');
    $session_id = (string) $result[0]->attributes()->id;
    $notch = (string) $result[0];
    unset($xml_object);
    
    ############################
    ## perform search
    ############################
    $operator = mysql_escape_mimic($_GET['mdo_andor']);
    $option = mysql_escape_mimic($_GET['mdo_wordbegin']);
    $field = mysql_escape_mimic($_GET['mdo_titletext']);
    $search = mysql_escape_mimic($_GET['mdo_search']);
    
    $data = '<search fields="titel,text">';
    $data .= '<condition';
    if(!empty($operator)) {
      $data .= ' operator="' . $operator . '"';
    }
    if(!empty($option)) {
      $data .= ' option="' . $option . '"';
    }
    if(empty($field)) {
      $page->add('succes', 'false');
    } else {
      $data .= ' field="' . $field . '"';
    }
    $data .= '>' . $search . '</condition>';
    $data .= '</search>';
    curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array('xmlstatement' => $data));
    $response = curl_exec($curl_handler);
    if(!$response) {
      $page->add('success', 'false');
    } else {
      $xml_object = simplexml_load_string($response);
      $result = $xml_object->xpath('/result/r');
      $retour = array();
      foreach($result as $item) {
        $retour[] = array(  'identifier'  => (string) $item->attributes()->identifier,
                            'title'       => utf8_decode(html_entity_decode((string) $item->f[0])),
                            'text'        => utf8_decode(html_entity_decode((string) $item->f[1])));
      }
      unset($xml_object);
      $page->add('success', 'true');
      $page->add('results', $retour);
    }
  }
} else {
  $page->add('success', 'false');
}
?>