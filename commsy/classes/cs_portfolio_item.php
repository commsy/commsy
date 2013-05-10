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

   function cs_portfolio_item ($environment) {
      $this->cs_item($environment);
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
   	  $value = $converter->sanitize($value);
      $this->_setValue('title', $value);
   }

   function getDescription () {
      return $this->_getValue('description');
   }

   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitize($value);
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
}
?>