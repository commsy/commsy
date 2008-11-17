<?PHP
class cs_class_factory {

   protected $_class_folder = NULL;

   public function __CONSTRUCT () {
       $class_config = array();
       include('etc/config_classes.php');
       $this->_class_array = $class_config;
   }

   public function getClass ( $name, $params = array() ) {
      $this->includeClass($name);
      return new $this->_class_array[$name]['name']($params);
   }

   public function includeClass ( $name ) {
      if ( empty($this->_class_array[$name]['folder']) ) {
         trigger_error('don\'t know where class '.$name.' is',E_USER_ERROR);
      } elseif ( empty($this->_class_array[$name]['filename']) ) {
         trigger_error('don\'t know the filename of '.$name,E_USER_ERROR);
      } elseif ( !file_exists($this->_class_array[$name]['folder'].$this->_class_array[$name]['filename']) ) {
         trigger_error('file '.$this->_class_array[$name]['folder'].$this->_class_array[$name]['filename'].' does not exist',E_USER_ERROR);
      } else {
         include_once($this->_class_array[$name]['folder'].$this->_class_array[$name]['filename']);
      }
   }
}
?>