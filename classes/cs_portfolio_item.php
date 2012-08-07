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
      $this->_setValue('title', $value);
   }

   function getUserID () {
      return $this->_getValue('title');
   }

   function setUserID ($value) {
      $this->_setValue('title', $value);
   }

   function getRows () {
      return $this->_getValue('rows');
   }

   function setRows ($value) {
      $this->_setValue('rows', $value);
   }

   function getColumns () {
      return $this->_getValue('columns');
   }

   function setColumns ($value) {
      $this->_setValue('columns', $value);
   }

   function getDescription () {
      return $this->_getValue('description');
   }

   function setDescription ($value) {
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

}
?>