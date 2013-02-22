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

/** cs_auth_item is needed to create auth items
 */
include_once('classes/cs_auth_item.php');
include_once('classes/cs_auth_manager.php');

/** class for database connection to a CAS-server
 * this class implements a manager for CAS authentication
 */
class cs_auth_cas extends cs_auth_manager {

    private $_host = '';
    private $_path = '';
    private $_dberror = '';

    /*
     * Translation Object
     */
    private $_translator = null;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param string server url to ldap-server
    * @param string baseuser information about baseuser
    */
    function cs_auth_cas () {
       global $environment;
       $this->_translator = $environment->getTranslationObject();
    }

   /** set auth source item
    * this method set the auth source item with information to contact the CAS Server
    *
    * @param object item auth source item
    */
    public function setAuthSourceItem ($item) {
       parent::setAuthSourceItem($item);
       $this->_host = $this->_auth_data_array['HOST'];
       $this->_path = $this->_auth_data_array['PATH'];
    }

   /** is the account granted ?
    * this method returns a boolean, if the account is granted in CAS.
    *
    * @param string uid user id of the current user
    * @param string password the password of the current user
    *
    * @return boolean true, account is granted via CAS
    *                 false, account is not granted via CAS
    */
   function checkAccount ($uid, $password) {
      $retour = false;

      $jsession_id = $this->_getJSessionID();
      $login_ticket = $this->_getLoginTicket($jsession_id);
      $commsy_url = $this->_getCommSyURL();
      $url = $this->_host.$this->_path.'/login;jsessionid='.$jsession_id.'?lt='.$login_ticket.'&username='.$uid.'&password='.$password.'&_eventId=submit';

      $page = get_headers($url);
      if ( !empty($page) ) {
         foreach ( $page as $value ) {
            if (strstr($value,'CASTGC')) {
               preg_match('~CASTGC=[a-zA-Z0-9-]+;~u',$value,$treffer);
               $cas_tgc = $treffer[0];
               $cas_tgc = mb_substr($cas_tgc,7);
               $cas_tgc = mb_substr($cas_tgc,0,mb_strlen($cas_tgc)-1);
               preg_match('~Path=[/a-zA-Z0-9-]+;~u',$value,$treffer);
               $cas_path = $treffer[0];
               $cas_path = mb_substr($cas_path,5);
               $cas_path = mb_substr($cas_path,0,mb_strlen($cas_path)-1);
               setcookie('CASTGC', $cas_tgc, 0, $cas_path, '', 0);
               $retour = true;
            }
         }
         if ( !isset($retour) or empty($retour) ) {
            $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('checkAccount: can not get headers from cas server to authenticate the user_id ['.$uid.'].'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
      }
      return $retour;
   }

   /** get CommSy URL - PRIVATE
    * this method returns a string -> the commsy URL.
    * this is needed to transmit redirect point to CAS
    *
    * @return string URL to CommSy htdocs folder
    */
   private function _getCommSyURL () {
      $commsy_url = 'http';
      if ( isset($_SERVER["SERVER_PORT"]) and !empty($_SERVER["SERVER_PORT"]) and $_SERVER["SERVER_PORT"] == 443) {
         $commsy_url .= 's';
      }
      $commsy_url .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
      if ( isset($_SERVER['QUERY_STRING'])) {
         $param_array = explode('&',$_SERVER['QUERY_STRING']);
         foreach ($param_array as $key => $value) {
            if ( strstr($value,'ticket=') ) {
               unset($param_array[$key]);
            }
         }
         if ( !empty($param_array) ) {
            $commsy_url .= '?'.implode('&',$param_array);
         }
      }
      return $commsy_url;
   }

   /** is ticket okay?
    * this method validates a given ticket via CAS
    *
    * @param string ticket a CAS ticket
    *
    * @return boolean true, ticket is granted via CAS
    *                 false, ticket is not granted via CAS
    */
   public function validateTicket ( $ticket ) {
      $retour = false;
      $url = $this->_host.$this->_path.'/serviceValidate?service='.urlencode($this->_getCommSyURL()).'&ticket='.$ticket;
      $xml = file_get_contents($url);
      if ( !empty($xml) ) {
         if ( strstr($xml,'authenticationSuccess') ) {
            $pos1 = mb_strpos($xml,'<cas:user>');
            $retour = mb_substr($xml,$pos1+mb_strlen('<cas:user>'));
            $retour = trim(mb_substr($retour,0,mb_strpos($retour,'</')));
            if ( empty($retour) ) {
               include_once('functions/error_functions.php');
               trigger_error('validateTicket: can not get user_id from granted ticket.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
            }
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('validateTicket: can not get page from cas server to authenticate ticket ['.$ticket.'].'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
      }
      return $retour;
   }

   /** get ticket
    * this method returns the CAS ticket
    *
    * @return string ticket a CAS ticket
    */
   public function getAuthTicket () {
      return $this->_ticket;
   }

   /** get login ticket from CAS - PRIVATE
    * this method returns the CAS ticket from the CAS server to login
    *
    * @return string login ticket from CAS server
    */
   private function _getLoginTicket ($value) {
      $url = $this->_host.$this->_path.'/login';
      $url .= ';jsessionid='.$value;
      $page = file_get_contents($url);
      if ( !empty($page) ) {
         preg_match('~name="lt" value="[A-Za-z0-9-_]+"~u',$page,$treffer);
         if ( !empty($treffer[0]) ) {
            $ticket = $treffer[0];
            $retour = mb_substr($ticket,17,mb_strlen($ticket)-18);
         } else {
            include_once('functions/error_functions.php');
            trigger_error('_getLoginTicket: can not get login ticket from page the cas server send.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('_getLoginTicket: can not get page from cas server to receive a login ticket.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
      }
      return $retour;
   }

   /** get JSessionID from CAS - PRIVATE
    * this method returns a JSessionID from the CAS server to login
    *
    * @return string JSessionID form CAS Server
    */
   private function _getJSessionID () {
      $retour = '';
      $url = $this->_host.$this->_path.'/login';
      $page = get_headers($url);
      if ( !empty($page) ) {
         foreach ( $page as $value ) {
            if (strstr($value,'JSESSIONID')) {
               preg_match('~JSESSIONID=[A-Z0-9]+;~u',$value,$treffer);
               $ticket = $treffer[0];
               $retour = mb_substr($ticket,11,mb_strlen($ticket)-12);
            }
         }
         if ( !isset($retour) or empty($retour) ) {
            include_once('functions/error_functions.php');trigger_error('_getJSessionID: can not get jsession id from headers the cas server send.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
         }
      } else {
         include_once('functions/error_functions.php');trigger_error('_getJSessionID: can not get headers from cas server to receive a jsession id.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
      }
      return $retour;
   }

   /** delete
    * only for upper class
    */
   public function delete ( $value ) {
   }

   /** save
    * only for upper class
    */
   public function save ( $value ) {
   }

   /** setCommSyIDLimit
    * only for upper class
    */
   public function setCommSyIDLimit ( $value ) {
   }

   /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error text
    */
   function getErrorMessage () {
      return $this->_dberror;
   }
}
?>