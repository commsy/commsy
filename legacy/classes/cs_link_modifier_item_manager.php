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

include_once 'classes/cs_link_father_manager.php';

/** class for database connection to the database table "link_modifier_item"
 * this class implements a database manager for the table "link_modifier_item",
 * in which we store who had edited an item.
 */
class cs_link_modifier_item_manager extends cs_link_father_manager
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'link_modifier_item';
    }

    /** This method returns all user_id's from people, who have edited this item.
     *
     * @param int item_id    id of the item
     *
     * @return array containing modifier id's
     */
    public function getModifiersOfItem($item_id)
    {
        $link_modifiers = [];
        $query = 'SELECT t2.item_id '.
                  'FROM '.$this->addDatabasePrefix('link_modifier_item').' AS t1, '.$this->addDatabasePrefix('user').' AS t2 '.
                  'WHERE t1.item_id = "'.encode(AS_DB, $item_id).'" AND t1.modifier_id = t2.item_id '.
                  'ORDER BY lastname ASC';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting modifiers: "'.$this->_dberror.'" from query: "'.$query.'"');
        } else {
            $link_modifiers = [];
            foreach ($result as $rs) {
                $link_modifiers[] = $rs['item_id'];
            }
        }

        return $link_modifiers;
    }

     /** mark an item as edited by the current user.
      *
      * @param int item_id    id of the item
      * @param int user_id    id of modifier, default set to id of current user
      */
     public function markEdited($item_id, $user_id = '')
     {
         if ('' == $user_id) {
             $user_id = $this->_current_user_id;
         }

         if (!empty($user_id)) {
             $query = '
                INSERT INTO '.$this->addDatabasePrefix('link_modifier_item').' SET '.
                 ' item_id="'.encode(AS_DB, $item_id).'", '.
                 ' modifier_id="'.encode(AS_DB, $user_id).'"'.
                 ' ON DUPLICATE KEY UPDATE'.
                 ' item_id="'.encode(AS_DB, $item_id).'", '.
                 ' modifier_id="'.encode(AS_DB, $user_id).'"
            ';

             $this->_db_connector->performQuery($query);

             $errno = $this->_db_connector->getErrno();
             if (!empty($errno)) {
                 include_once 'functions/error_functions.php';
                 trigger_error('Problems marking item as modified from query: "'.$query.'"');
             }
         }
     }

    public function mergeAccounts($account_new, $account_old)
    {
        $query_test = 'SELECT * FROM '.$this->addDatabasePrefix('link_modifier_item').' WHERE modifier_id = "'.encode(AS_DB, $account_old).'";';
        $result_test = $this->_db_connector->performQuery($query_test);
        if (!empty($result_test)) {
            foreach ($result_test as $row_test) {
                $query_test2 = 'SELECT * FROM '.$this->addDatabasePrefix('link_modifier_item').' WHERE modifier_id="'.encode(AS_DB, $account_new).'" and item_id="'.encode(AS_DB, $row_test['item_id']).'";';
                $result_test2 = $this->_db_connector->performQuery($query_test2);
                if (empty($result_test2)) {
                    $query = 'UPDATE '.$this->addDatabasePrefix('link_modifier_item').' SET ';
                    $query .= ' modifier_id = '.encode(AS_DB, $account_new);
                    $query .= ' WHERE modifier_id = '.encode(AS_DB, $account_old);
                    $query .= ' AND item_id = '.encode(AS_DB, $row_test['item_id']);

                    $result = $this->_db_connector->performQuery($query);
                    if (!isset($result) or !$result) {
                        include_once 'functions/error_functions.php';
                        trigger_error('Problems creating link_modifier_item from query: "'.$query.'"', E_USER_WARNING);
                    }
                } else {
                    $query = 'DELETE FROM '.$this->addDatabasePrefix('link_modifier_item');
                    $query .= ' WHERE modifier_id = '.encode(AS_DB, $account_old);
                    $query .= ' AND item_id = '.encode(AS_DB, $row_test['item_id']);

                    $result = $this->_db_connector->performQuery($query);
                    if (!isset($result) or !$result) {
                        include_once 'functions/error_functions.php';
                        trigger_error('Problems creating link_modifier_item from query: "'.$query.'"', E_USER_WARNING);
                    }
                }
            }
        }
    }

     public function deleteFromDb($context_id)
     {
         $id_array = [];

         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         $temp_user = $user_list->getFirst();
         while ($temp_user) {
             $id_array[] = $temp_user->getItemID();
             $temp_user = $user_list->getNext();
         }

         if (!empty($id_array)) {
             $query = 'DELETE FROM '.$this->_db_table.' WHERE '.$this->_db_table.'.modifier_id IN ('.implode(',', $id_array).')';
             $this->_db_connector->performQuery($query);
         }
     }
}
