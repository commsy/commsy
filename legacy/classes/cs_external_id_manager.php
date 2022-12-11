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

/** cs_list is needed for storage of the commsy items.
 */
include_once 'classes/cs_list.php';

/** cs_dates_item is needed to create dates items.
 */
include_once 'classes/cs_dates_item.php';
include_once 'functions/text_functions.php';
include_once 'functions/date_functions.php';

/** class for database connection to the database table "dates"
 * this class implements a database manager for the table "dates".
 */
class cs_external_id_manager extends cs_manager
{
    /**
     * string - containing the source-system as a limit.
     */
    public $_source_limit = null;

    /**
     * integer - containing an external id.
     */
    public $_external_id_limit = null;

    /**
     * integer - containing a commsy id.
     */
    public $_commsy_id_limit = null;

    /** constructor
     * the only available constructor, initial values for internal variables
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'external2commsy_id';
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $_source_limit = null;
        $_external_id_limit = null;
        $_commsy_id_limit = null;
    }

    /** set source limit
     * this method sets a source limit.
     *
     * @param string limit source
     */
    public function setSourceLimit($limit)
    {
        $this->_source_limit = (string) $limit;
    }

    /** set system limit
     * this method sets a system limit.
     *
     * @param string limit system
     */
    public function setSystemLimit($limit)
    {
        $this->_source_limit = (string) $limit;
    }

    public function setCommSyIdLimit($limit)
    {
        $this->_commsy_id_limit = (int) $limit;
    }

    public function setExternalIdLimit($limit)
    {
        $this->_external_id_limit = (int) $limit;
    }

    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.commsy_id) as count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.commsy_id';
        } elseif ('distinct' == $mode) {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }

        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table).'';

        $query .= ' WHERE 1';

        // fifth, insert limits into the select statement
        if (isset($this->_commsy_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB, $this->_commsy_id_limit).'"';
        }
        if (isset($this->_source_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB, $this->_source_limit).'"';
        }
        if (isset($this->_external_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB, $this->_external_id_limit).'"';
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting from '.$this->_db_table.'.', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    public function addIDsToDB($source, $external_id, $commsy_id)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' VALUES ("'.encode(AS_DB, $external_id).'","'.encode(AS_DB, $source).'","'.encode(AS_DB, $commsy_id).'")';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting from '.$this->_db_table.'.', E_USER_WARNING);
        }
    }

    public function getCommSyId($source, $external_id)
    {
        $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.commsy_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB, $source).'" AND '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB, $external_id).'"';
        $this->_last_query = $query;
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting from '.$this->_db_table.'.', E_USER_WARNING);
        } elseif (!empty($result[0]['commsy_id'])) {
            return $result[0]['commsy_id'];
        } else {
            return null;
        }
    }

    public function getExternalId($source, $commsy_id)
    {
        $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.external_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB, $source).'" AND '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB, $commsy_id).'"';
        $this->_last_query = $query;
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting from '.$this->_db_table.'.', E_USER_WARNING);
        } elseif (!empty($result[0]['external_id'])) {
            return $result[0]['external_id'];
        } else {
            return null;
        }
    }

    public function deleteByExternalId($external_id, $source_system)
    {
        $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB, $external_id).'" AND '.$this->addDatabasePrefix($this->_db_table).'.source_system = "'.encode(AS_DB, $source_system).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems deleting from '.$this->_db_table.'.', E_USER_WARNING);
        }
    }

    public function deleteByCommSyId($iid)
    {
        $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB, $iid).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems deleting from '.$this->_db_table.'.', E_USER_WARNING);
        }
    }
}
