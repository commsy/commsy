<?PHP
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

/** upper class of the text view
 */
$this->includeClass(VIEW);

/** class for a text view in commsy-style
 * this class implements a text view
 */
class cs_update_view extends cs_view {

   /**
    * string - containing the script to run
    */
   private $_script = '';

   /**
    * string - containing the folder
    */
   private $_folder = '';

   /**
    * string - containing the base path
    */
   private $_path = '';

   private $_db = NULL;

   private $_cached_sql = array();

   private $_points = 200;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function __construct ($params) {
      cs_view::__construct($params);
      $this->_title = 'update';
   }

   public function setScript ($value) {
      $this->_script = $value;
   }

   public function setFolder ($value) {
      $this->_folder = $value;
   }

   public function setPath ($value) {
      $this->_path = $value;
   }

   private function _flushHTML ( $value ) {
      echo($value);
      flush();
      return LF;
   }

   private function _flushHeadline ( $value ) {
      echo('<h3>'.$value.'</h3>');
      flush();
      return LF;
   }

   private function _getScriptWithPath () {
      return $this->_path.'/'.$this->_folder.'/'.$this->_script;
   }

   private function _initProgressBar($count,$title = 'Total entries to be processed',$value = '100%') {
      echo (BRLF.$title.": ".$count.LF);
      echo (BRLF."|");
      for ($i=1;$i<=$this->_points;$i++) {
          echo('.');
      }
      echo ("| ".LF);
      echo ('<script type="text/javascript">window.scrollTo(1,10000000);</script>'.LF);
      echo BRLF."|";
      flush();
   }

   private function _updateProgressBar($total) {
      static $counter_upb = 0;
      static $percent = 0;
      $counter_upb++;
      $cur_percent = (int)(($counter_upb*$this->_points)/($total) );
      if ($percent < $cur_percent) {
         $add = $cur_percent-$percent;
         while ($add>0) {
            $add--;
            echo(".");
         }
         $percent = $cur_percent;
         flush();
      }
      if ($counter_upb==$total) {
         $counter_upb = 0;
         $percent = 0;
         echo('|');
         flush();
      }
   }

   private function _getMicroTime() {
      list($usec, $sec) = explode(' ', microtime());
      return ((float)$usec + (float)$sec);
   }

   private function _getProcessedTimeInHTML ($time_start) {
      $time_end = $this->_getMicroTime();
      $time = round($time_end - $time_start,3);
      $retour = BRLF."Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60)).BRLF.BRLF;
      return $retour;
   }

   private function _getSuccessAsHTML ($value) {
      $retour = '';
      if ( $value ) {
         $retour = ' <span style="color: green;">[done]</span>';
      } else {
         $retour = ' <span style="color: red;">[error]</span>';
      }
      return $retour;
   }

   private function _getDBConnector () {
      if ( !isset($this->_db) ) {
         $this->_db = $this->_environment->getDBConnector();
      }
      return $this->_db;
   }

   private function _select ( $sql, $quiet = false ) {
      $db = $this->_getDBConnector();
      if ( $quiet ) {
         $db->setDisplayOff();
      } else {
         $db->setDisplayOn();
      }
      return $db->performQuery($sql);
   }

   private function _existsField ( $table, $field ) {
      $retour = false;
      $sql = 'SHOW COLUMNS FROM '.$table;
      if ( empty($this->_cached_sql[$sql]) ) {
         $result = $this->_select($sql);
         $this->_cached_sql[$sql] = $result;
      } else {
         $result = $this->_cached_sql[$sql];
      }
      foreach ( $result as $field_array ) {
         if ( !empty($field_array)
              and !empty($field_array['Field'])
              and $field_array['Field'] == $field
            ) {
            $retour = true;
            break;
         }
      }
      return $retour;
   }

   private function _existsIndex ( $table, $field ) {
      $retour = false;
      $sql = 'SHOW INDEX FROM '.$table;
      $result = $this->_select($sql);
      foreach ( $result as $field_array ) {
         if ( !empty($field_array)
              and !empty($field_array['Column_name'])
              and $field_array['Column_name'] == $field
            ) {
            $retour = true;
            break;
         }
      }
      return $retour;
   }

   private function _existsTable ( $table ) {
      $retour = false;
      $sql = 'SHOW TABLES LIKE "'.$table.'"';
      $result = $this->_select($sql);
      if(!empty($result)){
         $retour = true;
      }
      return $retour;
   }

   /** get content of plugin as HTML
    * this method returns the content of the plugin in HTML-Code
    *
    * @return string content as HMTL
    */
   public function asHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF UPDATE asHTML -->'.LF;
      $html = $this->_flushHTML($html);

      // time management for this script
      $time_start = $this->_getMicroTime();

      include_once($this->_getScriptWithPath());

      // end update script
      if ( isset($success) ) {
         $this->_flushHTML($this->_getSuccessAsHTML($success));
      }
      $this->_flushHTML($this->_getProcessedTimeInHTML($time_start));

      $html .= '<!-- END OF UPDATE asHTML -->'.LF.LF;
      $html = $this->_flushHTML($html);
      return $html;
   }
}
?>