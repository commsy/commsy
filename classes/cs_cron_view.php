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

/**
 *  class for CommSy view: cron
 */
class cs_cron_view {

   /** data to display
    */
   var $_cron_result_data = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    */
   function cs_cron_view () {
   }

  /** this method sets the cron result data
   *
   * @param array cron result data
   */
   function setCronResult($data_array) {
      $this->_cron_result_data = $data_array;
   }

   /** get view as HTML
    * this method returns the view in HTML-Code
    *
    * @return string view as HMTL
    */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF CRON VIEW -->'.LF;
      $html .= '<h1>CommSy Cron Jobs</h1>'.LF;
      if (count($this->_cron_result_data) == 0) {
         $html .= 'there are no cron jobs configurated';
      } else {
         foreach ($this->_cron_result_data as $room_status => $value) {
            $html .= '<h2>'.$room_status.'</h2>'.LF;
            foreach ($value as $room) {
               $html .= '<h3>'.$room['title'].'</h3>'.LF;
               foreach ($room['crons'] as $cron_status => $crons) {
                  $html .= '<table border="0" summary="Layout">'.LF;
                  $html .= '<tr>'.LF;
                  $html .= '<td style="vertical-align:top; width: 4em;">'.LF;
                  $html .= '<span style="font-weight: bold;">'.$cron_status.'</span>'.LF;
                  $html .= '</td>'.LF;
                  $html .= '<td>'.LF;
                  if ( !empty($crons) ) {
                     foreach ($crons as $cron) {
                        $html .= '<div>'.LF;
                        $html .= '<span style="font-weight: bold;">'.$cron['title'].'</span>'.BRLF;
                        if (!empty($cron['description'])) {
                           $html .= $cron['description'];
                           if ($cron['success']) {
                              $html .= ' [<font color="#00ff00">done</font>]'.BRLF;
                           } else {
                              $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
                           }
                        }
                        if ( !empty($cron['success_text']) ) {
                           $html .= $cron['success_text'].BRLF;
                        }
                        $html .= '</div>'.LF;
                     }
                  } else {
                     $html .= 'no crons defined';
                  }
                  $html .= '</td>'.LF;
                  $html .= '</tr>'.LF;
                  $html .= '</table>'.LF;
               }
               $html .= BRLF;
            }
         }
      }
      $html .= '<!-- END OF CRON VIEW -->'.LF.LF;
      return $html;
   }

   /** for needed as view, see upper class
    */
   function getInfoForHeaderAsHTML () {
   }

   /** for needed as view, see upper class
    */
   function getInfoForBodyAsHTML () {
   }
}
?>