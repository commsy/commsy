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

/** upper class of the log manager.
 */
include_once 'classes/cs_manager.php';

/** class for database connection to the database table "log_error"
 * this class implements a database manager for the table "log_error".
 */
class cs_log_error_manager extends cs_manager
{
    /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        cs_manager::__construct($environment);
        $this->_db_table = 'log_error';
    }

    /** reset limits
     * reset limits of this class: room limit, delete limit.
     */
    public function resetLimits()
    {
    }

    public function select()
    {
        $result = $this->_performQuery('select');
        $array = [];
        foreach ($result as $row) {
            $array[] = $row;
        }

        return $array;
    }

    public function count()
    {
        $retour = 0;
        $result = $this->_performQuery('count');
        $row = $result[0];
        $retour = $row['count'];

        return $retour;
    }

    public function delete()
    {
        return $this->_performQuery('delete');
    }

    public function deleteByArray($array)
    {
        $id_string = '';
        $first = true;
        foreach ($array as $row) {
            if (!empty($row['id'])) {
                if ($first) {
                    $first = false;
                } else {
                    $id_string .= ',';
                }
                $id_string .= $row['id'];
            }
        }

        $query = '';
        $query .= 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE id IN ('.$id_string.')';

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems at logs from query:<br />"'.$query.'"', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    public function _performQuery($mode = 'select')
    {
        if ('select' == $mode) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
        } elseif ('delete' == $mode) {
            $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table);
        } elseif ('count' == $mode) {
            $query = 'SELECT count(id) AS count FROM '.$this->addDatabasePrefix($this->_db_table);
        } else {
            include_once 'functions/error_functions.php';
            trigger_error('lost perform mode', E_USER_ERROR);
        }

        $query .= ' WHERE 1';

        $query .= ' ORDER BY datetime ASC';

        if (isset($this->_limit_from) and isset($this->_limit_range)) {
            if (empty($this->_limit_form)) {
                $query .= ' LIMIT 0,'.encode(AS_DB, $this->_limit_range);
            } else {
                $query .= ' LIMIT '.encode(AS_DB, $this->_limit_from).','.encode(AS_DB, $this->_limit_range);
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems log from query: "'.$query.'"', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    public function saveArray($array)
    {
        $retour = false;
        if (!isset($array['number'])) {
            $array['number'] = '';
        }
        if (!isset($array['type'])) {
            $array['type'] = '';
        }
        if (!isset($array['message'])) {
            $array['message'] = '';
        }
        if (!isset($array['file'])) {
            $array['file'] = '';
        }
        if (!isset($array['line'])) {
            $array['line'] = '';
        }
        if (!isset($array['context'])) {
            $array['context'] = '';
        }
        if (!isset($array['module'])) {
            $array['module'] = '';
        }
        if (!isset($array['function'])) {
            $array['function'] = '';
        }
        if (!isset($array['user'])) {
            $array['user'] = '';
        }

        // mysql - replication
        $delayed = ' DELAYED ';
        $db_replication = $this->_environment->getConfiguration('db_replication');
        if (!empty($db_replication)
             and $db_replication
        ) {
            $delayed = ' ';
        }
        $query = 'INSERT'.$delayed.'INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'datetime=NOW(), '.
                 'number="'.encode(AS_DB, $array['number']).'", '.
                 'type="'.encode(AS_DB, $array['type']).'", '.
                 'message="'.encode(AS_DB, $array['message']).'", '.
                 'file="'.encode(AS_DB, $array['file']).'", '.
                 'line="'.encode(AS_DB, $array['line']).'", '.
                 'context="'.encode(AS_DB, $array['context']).'", '.
                 'module="'.encode(AS_DB, $array['module']).'", '.
                 'function="'.encode(AS_DB, $array['function']).'", '.
                 'user="'.encode(AS_DB, $array['user']).'"';
        $result = $this->_db_connector->performQuery($query);
        if (isset($result)) {
            $retour = true;
        } else {
            include_once 'functions/error_functions.php';
            trigger_error('Problems save '.$this->_db_table.' with query: "'.$query.'"', E_USER_WARNING);
        }

        return $retour;
    }
}
