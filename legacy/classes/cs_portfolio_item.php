<?PHP
// $Id$
//
// Release $Name$
//

/** upper class of the announcement item
 */
include_once('classes/cs_item.php');

/** class for a portfolio
 * this class implements a portfolio item
 */
class cs_portfolio_item extends cs_item {

   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = CS_PORTFOLIO_TYPE;
   }

   function _setItemData($data_array) {
      // not yet implemented
      $this->_data = $data_array;
   }

   function getTitle () {
      return $this->_getValue('title');
   }

   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeHTML($value);
      $this->_setValue('title', $value);
   }

   function getDescription () {
      return $this->_getValue('description');
   }

   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeFullHTML($value);
      $this->_setValue('description', $value);
   }

   function save() {
      $portfolio_manager = $this->_environment->getPortfolioManager();
      $this->_save($portfolio_manager);
   }

   function delete() {
      $manager = $this->_environment->getPortfolioManager();
      $this->_delete($manager);
   }
   
   function getExternalViewer() {
   	return $this->_getValue("externalViewer");
   }
   
   function setExternalViewer($userIdArray) {
   	$this->_setValue("externalViewer", $userIdArray);
   }
   
   function setTemplate() {
   	$this->_setValue("template", '1');
   }
   
   function unsetTemplate() {
   	$this->_unsetValue("template");
   }
   
   function getTemplate() {
   	return $this->_getValue("template");
   }
   
   function isTemplate() {
   	$flag = false;
   	if($this->_getValue("template") == 1){
   		$flag = true;
   	}
   	return $flag;
   }
   
   function setExternalTemplate($userIdArray) {
   	$this->_setValue("externalTemplate", $userIdArray);
   }
   
   function getExternalTemplate() {
   	return $this->_getValue("externalTemplate");
   	
   }
}
?>