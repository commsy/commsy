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

/*
 * inherit from text view
 */
$this->includeCLASS(TEXT_VIEW);

/**
 * Class for Out of Service-View
 * @author Christoph Schönfeld
 */
class cs_outofservice_view extends cs_text_view {
   /** constructor: cs_text_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
   */
   function __construct($params) {
      cs_text_view::__construct($params);
   }
   
   /** get text view as HTML
    * this method returns the text view in HTML-Code
    *
    * @return string text view as HMTL
   */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF TEXTVIEW -->'.LF;
      if (!empty($this->_anchor)) {
         $html .= '<a name="'.$this->_anchor.'"></a>'.LF;
      }
      
      $params = $this->_environment->getCurrentParameterArray();
      
      if(!empty($params['root_login']) && $params['root_login'] == 1) {
         $params['target_cid'] = $this->_environment->getCurrentContextID(); 
         $html .= '<form style="margin:0px; padding:0px;" method="post" action="'.curl( $this->_environment->getCurrentContextID(),
         																				'context',
         																				'login',
         																				$params).'" name="login">'.LF;
         $html .= '<table summary="Layout">'.LF;
         $html .= '<tr><td style="padding:0px;margin:0px;">'.LF;
         $html .= $this->_translator->getMessage('MYAREA_ACCOUNT').':'.LF.'</td><td>';
         $html .= '<input type="text" name="user_id" size="100" style="font-size:10pt; width:6.2em;" tabindex="1"/>'.LF;
         $html .= '</td></tr>'.LF.'<tr><td>'.LF;
         $html .= $this->_translator->getMessage('MYAREA_PASSWORD').':'.'</td>'.LF.'<td>';
         $html .= '<input type="password" name="password" size="10" style="font-size:10pt; width:6.2em;" tabindex="2"/>'.'</td></tr>'.LF;
         $html .= '<tr>'.LF.'<td></td>'.LF.'<td>'.LF;
         $html .= '<input type="submit" name="option" style="width: 6.6em;" value="'.$this->_translator->getMessage('MYAREA_LOGIN_BUTTON').'" tabindex="4"/>'.LF;
         $html .= '</td></tr>'.LF;
         $html .= '</table>'.LF;
         $html .= '</form>'.LF;
      } else {
         $html .= '<table border="0" cellspacing="1" cellpadding="3" width="100%" summary="Layout">'.LF;
         $html .= '   <tr>'.LF;
         $html .= '	  <td style="text-align:right;">';
      
         $params['root_login'] = 1;
         $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
                           		'home',
                           		'outofservice',
                                $params,
                                $this->_translator->getMessage("SERVER_OUTOFSERVICE_ROOT_LOGIN"));
         $html .= '	  </td>'.LF;
         $html .= '   </tr>'.LF;
         $html .= '   <tr>'.LF;
         $html .= '      <td>'.$this->_text_as_html_long($this->_text).'</td>'.LF;
         $html .= '   </tr>'.LF;
         $html .= '</table>'.LF;
      }
      
      $html .= '<!-- END OF TEXTVIEW -->'.LF.LF;
      return $html;
   }
}

?>