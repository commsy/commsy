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
         $cURL = curl_init();
         curl_setopt($cURL, CURLOPT_URL, "www.life-url.de/logout/" . $seesion_id);
         curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
         global $c_proxy_ip;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip;
         }
         global $c_proxy_port;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip.':'.$c_proxy_port;
         }
         curl_setopt($cURL,CURLOPT_PROXY,$proxy);  
         $output = curl_exec($cURL);
         curl_close($cURL);
      // -------------------------------------
   }
   
   public function user_save($user_item){
      $changed = false;
      // + Kennung
      if($user_item->hasChanged('email') or 
         $user_item->hasChanged('firstname') or
         $user_item->hasChanged('lastname') or
         $user_item->hasChanged('user_id')){
            $email = $user_item->getEmail();
            $firstname = $user_item->getFirstname();
            $lastname = $user_item->getLastname();
            $user_id = $user_item->getUserID();
            $changed = true;
      }
      if($changed){
         $session = $this->_environment->getSessionItem();
         $seesion_id = $session->getSessionID();
      // -------------------------------------
      // ToDo: url an life anpassen
         $cURL = curl_init();
         curl_setopt($cURL, CURLOPT_URL, "www.life-url.de/changeuser/" . $seesion_id . '/' . $user_id . '/' . $firstname . '/' . $lastname . '/' . $email);
         curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
         global $c_proxy_ip;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip;
         }
         global $c_proxy_port;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip.':'.$c_proxy_port;
         }
         curl_setopt($cURL,CURLOPT_PROXY,$proxy);  
         $output = curl_exec($cURL);
         curl_close($cURL);
      // -------------------------------------
      }
   }
}
?>