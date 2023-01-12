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

/** class for database connection to the database table "project"
 * this class implements a database manager for the table "project".
 */
class cs_portal_manager extends cs_context_manager
{
    private ?string $_url_limit = null;

    /** constructor: cs_server_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        $this->_db_table = 'portal';
        $this->_room_type = CS_PORTAL_TYPE;
        cs_context_manager::__construct($environment);
    }

     /** reset limits
      * reset limits of this class.
      */
     public function resetLimits()
     {
         parent::resetLimits();
         $this->_url_limit = null;
     }

     /** set url limit.
      */
     public function setUrlLimit($limit)
     {
         $this->_url_limit = (string) $limit;
     }

     /** select communities limited by limits
      * this method returns a list (cs_list) of communities within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
      */
     public function _performQuery($mode = 'select')
     {
         if ('count' == $mode) {
             $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.id) AS count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.id';
         } else {
             $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
         }

         $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' WHERE 1';

         // insert limits into the select statement
         if (true == $this->_delete_limit) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
         }
         if (isset($this->_status_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB, $this->_status_limit).'"';
         }
         if (isset($this->_url_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.url LIKE "%'.encode(AS_DB, $this->_url_limit).'%"';
         }

         if (isset($this->_order)) {
             if ('date' == $this->_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
             } elseif ('creation_date' == $this->_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
             } elseif ('activity_rev' == $this->_order) {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
             } else {
                 $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
             }
         } else {
             $query .= ' ORDER BY title, modification_date DESC';
         }

         if ('select' == $mode) {
             if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                 $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
             }
         }

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"', E_USER_ERROR);
         } else {
             return $result;
         }
     }
}
