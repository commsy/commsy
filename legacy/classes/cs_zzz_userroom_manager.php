<?PHP

include_once('classes/cs_userroom_manager.php');

/**
 * implements a database manager for items in table "zzz_room" with type "userroom"
 */
class cs_zzz_userroom_manager extends cs_userroom_manager {
    public function __construct ($environment) {
        global $symfonyContainer;
        $this->_db_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix').'_';
        
        parent::__construct($environment);
    }
}
?>
