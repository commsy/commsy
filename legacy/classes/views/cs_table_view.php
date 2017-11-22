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

/** upper class of the detail view
 */
$this->includeClass(VIEW);

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** class for a table view of commsy items
 * this class implements a table view of commsy itema. A table view consist a headline, data and actions
 */
class cs_table_view extends cs_view {

   /**
    * string - containing the title of the detail view
    */
   var $_title = NULL;

   /**
    * string - containing the description of the detail view
    */
   var $_description = NULL;

   /**
    * array - containing the columns of the detail view
    */
   var $_columns = array();

   /**
    * array - containing the actions of the detail view
    */
   var $_actions = array();

   /**
    * array - containing the data of the detail view
    */
   var $_data = array();

   /**
    * boolean - true if a title row will be painted, false if not
    */
   var $_paint_title_row = TRUE;

   /**
    * boolean - true if the backgroundcolor is the same as the color of the table body
    */
   var $_tablebody_color_is_background_color = FALSE;

   /**
    * boolean - true if tablehead should not be displayed
    */
   var $_without_tablehead = FALSE;


   var $_first_row='';
   var $_second_row='';

   /** constructor: cs_table_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   /** set title of the table view
    * this method sets the title of the table view
    *
    * @param string value title of the table view
    *
    * @author CommSy Development Group
    */
   function setTitle ($value) {
      $this->_title = (string)$value;
   }

   function getTitle ($value) {
      return $this->_title;
   }

   /** set description of the table view
    * this method sets the description of the table view
    *
    * @param string value description of the table view
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
      if (!empty($value)) {
         $this->_description = '('.(string)$value.')';
      } else {
         $this->_description = '';
      }
   }

   /** add an action to table view
    * this method adds an action (hyperlink) to the detail view
    *
    * @param string  title        title of the action
    * @param boolean modifying    is this a modifying action ?
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
    *
    * @version CommSy 2.0
    * @author CommSy Development Group
    */
   function addAction ($title, $modifying = false, $explanation = '', $module = '', $function = '', $parameter = '') {
      $action['title'] = $title;
      $action['modifying'] = $modifying;
      $action['module'] = $module;
      $action['function'] = $function;
      $action['parameter'] = $parameter;
      $action['explanation'] = $explanation;
      $this->_actions[] = $action;
   }

   /** add a column to the table view
    * this method adds a column to the table view
    *
    * @param string  title            title of the column
    * @param boolean sortable         true, if column is sortable - false, if not
    * @param boolean is_sort_criteria true, if column is the current sortcriteria
    * @param string  explanantion     explanation of the action
    * @param string  parameter        get parameter of the action
    * @param integer width            width of the column
    *
    * @version CommSy 2.0
    * @author CommSy Development Group
    */
   function addColumn ($title, $sortable = false, $is_sort_criteria = false, $explanation = '', $parameter = '', $width = '') {
      $column['title']            = $title;
      $column['sortable']         = $sortable;
      $column['is_sort_criteria'] = $is_sort_criteria;
      $column['parameter']        = $parameter;
      $column['explanation']      = $explanation;
      $column['width']            = $width;
      $this->_columns[]           = $column;
   }

   /** add a row to the table view
    * this method adds a row (data) to the table view
    *
    * @param array row data of a row
    *
    * @author CommSy Development Group
    */
   function addRow ($row) {
      $this->_data[] = (array)$row;
   }

   function paintTitleRowOn() {
      $this->_paint_title_row = TRUE;
   }

   function paintTitleRowOff() {
      $this->_paint_title_row = FALSE;
   }

   function tableColorIsBackgroundColor($isIt) {
      $this->_tablebody_color_is_background_color = $isIt;
   }

   function paintWithoutTablehead() {
      $this->_without_tablehead = true;
   }

   /** get table view as HTML
    * this method returns the table view in HTML-Code
    *
    * @return string table view as HMTL
    *
    * @version CommSy 2.0
    * @author CommSy Development Group
    */
   function asHTML () {


    $html  = '';
    $html .= '<!-- begin of tableview -->'."\n";
    $html .= '<table border="0" cellspacing="1" cellpadding="3" width="100%" summary="Layout">'."\n";
    if ($this->_paint_title_row == true  ) {
            $html .= '   <tr><td class="tabletitle" colspan="'.count($this->_columns).'">'."\n";
            $html .= '      <table border="0" cellspacing="0" cellpadding="2" width="100%" summary="Layout">'."\n";
            $html .= '         <tr><td class="tabletitle">'."\n";
            $html .= '            <b>'.$this->_title.'</b>'."\n";
            $html .= '            <span class="small">'.$this->_description.'</span>'."\n";
            $html .= '         </td><td class="tableactions" align="right">'."\n";

              $first = true;
              foreach ($this->_actions as $action) {
                 $html .= '            ';
                 if (!$first) {
                    $html .= '| ';
                 } else {
                    $first = false;
                 }
                 if (empty($action['module']) or empty($action['function'])) {
                    $html .= '<span class="disabled">'.$action['title'].'</span>';
                 } elseif ($action['modifying'] == true and $this->_with_modifying_actions == false) {
                    $html .= '<span class="frozen">'.$action['title'].'</span>';
                 } else {
                    $html .= ahref_curl($this->_environment->getCurrentContextID(), $action['module'], $action['function'], $action['parameter'], $action['title'], $action['explanation']);
                 }
                 $html .= "\n";
              }

              $html .= '         </td></tr>'."\n";
              $html .= '      </table>'."\n";
              $html .= '   </td></tr>'."\n";
      }

      if (!$this->_without_tablehead) {
         $html .= '   <tr>'."\n";
         foreach ($this->_columns as $column) {
            $html .= '      <td class="tablehead"';
            if (!empty($column['width'])) {
               $html .= ' width="'.$column['width'].'"';
            }
            $html .= '>';
            if ($column['is_sort_criteria']) {
               $html .= $column['title'].' ^';
            } elseif (!$column['sortable']) {
               $html .= $column['title'];
            } else {
               $params = array();
               $params['from'] = '1';
               $params['sortby'] = $column['parameter'];
               $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $column['title'], $column['explanation']);
               unset($params);
            }
            $html .= '</td>'."\n";
         }
         $html .= '   </tr>'."\n";
      }

      foreach ($this->_data as $row) {
         $html .= '   <tr>'."\n";
         $check_array = $row[0];
         if (is_array($check_array)){
              for ($i=0; $i<count($this->_columns); $i++) {
                 $html .= '      <td bgcolor='.$row[$i]['bgcolor'].'>'.$row[$i]['text'].'</td>'."\n";
             }
         }
         else{
              for ($i=0; $i<count($this->_columns); $i++) {
                 if ($this->_tablebody_color_is_background_color == false) {
                    $html .= '<td class="tablebody">';
                 } else {
                    $html .= '<td class="tablebody_w_c">';
                 }
                 if (!empty($row[$i])) {
                    $html .= $row[$i];
                 } else {
                    $html .= '&nbsp;';
                 }
                 $html .= '</td>'."\n";
            }
         }
         $html .= '   </tr>'."\n";
      }

      $html .= '</table>'."\n";
      $html .= '<!-- end of tableview -->'."\n\n";
      return $html;
   }
}
?>