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
}
?>