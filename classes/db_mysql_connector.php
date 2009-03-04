<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2007 Iver Jackewitz
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

class db_mysql_connector {

   private $_db_link = NULL;
   private $_db_errno = NULL;
   private $_db_error = NULL;
   private $_query_array = array();
   private $_log_query = false;
   private $_display = true;
   private $_db_connect_data = array();

   public function __CONSTRUCT ($data) {
      $this->_db_link = mysql_connect($data['host'],$data['user'],$data['password'],true);
      if ( empty($this->_db_link) or !$this->_db_link ) {
         include_once('functions/error_functions.php');
         trigger_error('can not connect zu mysql database: '.$data['host'],E_USER_ERROR);
      } else {
         mysql_select_db($data['database'], $this->_db_link);
         mysql_query("SET NAMES 'utf8'");
         mysql_query("SET CHARACTER SET 'utf8'");
      }
   }

   public function performQuery ($query) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      $result = mysql_query($query,$this->_db_link);
      if ( $this->_log_query ) {
         $this->_query_array[] = $query;
      }
      $this->_db_errno = mysql_errno($this->_db_link);
      $this->_db_error = mysql_error($this->_db_link);
      $retour = NULL;

      if ( !empty($this->_db_errno) ) {
         if ( $this->_display ) {
            echo('<br/><hr/> **** DB - ERROR **** <br/>'."\n");
            echo('Error-Number: '.$this->_db_errno.'<br/>'."\n");
            echo('Error-Text: '.$this->_db_error.'<br/>'."\n");
            echo('Query: '.$query.'<br/><hr/>'."\n");
         }
      } else {
         if ( mb_substr(trim($query),0,6) == 'SELECT'
              or mb_substr(trim($query),0,4) == 'SHOW'
            ) {
            $retour = array();
            while ( $row = mysql_fetch_assoc($result) ) {
               $retour[] = $row;
            }
            mysql_free_result($result);
         } elseif ( mb_substr(trim($query),0,6) == 'INSERT' ) {
            if ( strstr($query,'INSERT INTO chat')
                 or strstr($query,'INSERT INTO auth')
                 or strstr($query,'INSERT INTO item_link_file')
                 or strstr($query,'INSERT INTO external2commsy_id')
                 or strstr($query,'INSERT INTO links')
                 or strstr($query,'INSERT INTO link_modifier_item')
                 or strstr($query,'INSERT INTO materials')
                 or strstr($query,'INSERT INTO noticed')
                 or strstr($query,'INSERT INTO reader')
                 or strstr($query,'INSERT INTO section')
               ) {
               $retour = $result;
            } else {
               $retour = mysql_insert_id($this->_db_link);
            }
         } else {
            $retour = $result;
         }
         unset($result);
      }
      unset($query);
      return $retour;
   }

   public function setLogQueries () {
      $this->_log_query = true;
   }

   public function getQueryArray () {
      return $this->_query_array;
   }

   public function getErrno () {
      return $this->_db_errno;
   }

   public function getError () {
      return $this->_db_error;
   }

   public function setDisplayOff () {
      $this->_display = false;
   }

   public function setDisplayOn () {
      $this->_display = true;
   }

   public function text_php2db ( $text ) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if ( get_magic_quotes_gpc() ) {
         $text = stripslashes($text);
      }
      $text = mysql_real_escape_string($text,$this->_db_link);
      return $text;
   }
}
?>