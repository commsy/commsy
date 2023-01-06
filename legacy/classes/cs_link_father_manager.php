<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for database connection to the database table "link_material_file"
 * this class implements a database manager for the table "link_material_file",
 * in which we store the links between materials and files.
 */
class cs_link_father_manager extends cs_manager
{
    public $_db_table = null;

    /**
     * object cs_user_item - containing the current user.
     */
    protected $_current_user_id;

    public $_context_limit = null;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct(cs_environment $environment)
    {
        parent::__construct($environment);
        $this->_current_user_id = $this->_current_user->getItemID();
    }

    /** reset limits
     * reset limits of this class.
     */
    public function resetLimits()
    {
        $this->_context_limit = null;
    }

    /** select items limited by limits
     * this method returns a list (cs_list) of items within the database limited by the limits.
     * depends on _performQuery(), which must be overwritten.
     */
    public function select()
    {
        $result = $this->_performQuery();
        $this->_id_array = null;
        $data = new cs_list();

        $result = is_array($result) ? $result : [];

        foreach ($result as $query_result) {
            $item = $this->_buildItem($query_result);
            $data->add($item);
        }

        $this->_data = $data;
    }

   public function setContextLimit($limit)
   {
       $this->_context_limit = $limit;
   }

   public function _updateFromBackup2($data_array)
   {
       $success = false;
       if (empty($data_array['item_vid'])) {
           $data_array['item_vid'] = 0;
       }
       if (!isset($data_array['deleter_id']) or empty($data_array['deleter_id'])) {
           $data_array['deleter_id'] = 'NULL';
       }
       if (!isset($data_array['deletion_date']) or empty($data_array['deletion_date'])) {
           $data_array['deletion_date'] = 'NULL';
       }

       $query = '';
       $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';

       $query .= ' SET ';
       $first = true;

       foreach ($data_array as $key => $value) {
           if ('item_iid' != $key
                and 'item_vid' != $key
                and 'file_id' != $key
           ) {
               if ($first) {
                   $first = false;
               } else {
                   $query .= ',';
               }
               if ('NULL' == $value) {
                   $query .= $key.'= NULL';
               } else {
                   $query .= $key.'="'.encode(AS_DB, $value).'"';
               }
           }
       }

       $query .= ' WHERE item_iid="'.encode(AS_DB, $data_array['item_iid']).'"';
       $query .= ' AND item_vid="'.encode(AS_DB, $data_array['item_vid']).'"';
       $query .= ' AND file_id="'.encode(AS_DB, $data_array['file_id']).'"';
       $query .= ';';

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problem backuping item from query: "'.$query.'"', E_USER_ERROR);
       } else {
           $success = true;
       }

       return $success;
   }
}
