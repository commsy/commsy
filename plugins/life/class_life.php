<?PHP
// plugin from effective WEBWORK GmbH

class class_life {

   private $_environment = NULL;
   private $_translator  = NULL;
   private $_identifier  = 'life'; // must be the same as in etc/commsy/plugin.php

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      $this->_environment = $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   public function isRubricPlugin () {
      return false;
   }
   
   public function logout(){
      $session = $this->_environment->getSessionItem();
      $seesion_id = $session->getSessionID();
      // -------------------------------------
      // ToDo: url an life anpassen
//      $cURL = curl_init();
//      curl_setopt($cURL, CURLOPT_URL, "www.life-url.de/?session_id=" . $seesion_id);
//      curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
//      $output = curl_exec($cURL);
//      curl_close($cURL);
      // -------------------------------------
   }
}
?>