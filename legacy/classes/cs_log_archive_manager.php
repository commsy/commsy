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

/** class for database connection to the database table "reader"
 * this class implements a database manager for the table "reader". Read items.
 */
class cs_log_archive_manager extends cs_manager
{
    /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

    public function save($data)
    {
        if (!is_array($data)) {
            trigger_error('need array', E_USER_ERROR);
            $success = false;
        } else {
            if (is_array($data[0])) {
                $success = true;
                foreach ($data as $key => $value) {
                    if (!isset($data[$key]['uid'])
                        or empty($data[$key]['uid'])
                    ) {
                        $data[$key]['uid'] = '0';
                    }
                    if (!isset($data[$key]['iid'])
                        or empty($data[$key]['iid'])
                    ) {
                        $data[$key]['iid'] = '0';
                    }
                    if (!isset($data[$key]['queries'])
                        or empty($data[$key]['queries'])
                    ) {
                        $data[$key]['queries'] = '0';
                    }
                    if (!isset($data[$key]['time'])
                        or empty($data[$key]['time'])
                    ) {
                        $data[$key]['time'] = '0';
                    }
                    $query = 'INSERT INTO '.$this->addDatabasePrefix('log_archive').' SET '.
                        'ip="'.encode(AS_DB, $data[$key]['ip']).'", '.
                        'agent="'.encode(AS_DB, $data[$key]['agent']).'", '.
                        'timestamp="'.encode(AS_DB, $data[$key]['timestamp']).'", '.
                        'request="'.encode(AS_DB, $data[$key]['request']).'", '.
                        'method="'.encode(AS_DB, $data[$key]['method']).'", '.
                        'uid="'.encode(AS_DB, $data[$key]['uid']).'", '.
                        'ulogin="'.encode(AS_DB, $data[$key]['ulogin']).'", '.
                        'cid="'.encode(AS_DB, $data[$key]['cid']).'", '.
                        'module="'.encode(AS_DB, $data[$key]['module']).'", '.
                        'fct="'.encode(AS_DB, $data[$key]['fct']).'", '.
                        'param="'.encode(AS_DB, $data[$key]['param']).'", '.
                        'iid="'.encode(AS_DB, $data[$key]['iid']).'", '.
                        'queries="'.encode(AS_DB, $data[$key]['queries']).'", '.
                        'time="'.encode(AS_DB, $data[$key]['time']).'"';

                    // perform query
                    $result = $this->_db_connector->performQuery($query);
                    if (!isset($result)) {
                        trigger_error('Problems log_archive from query:<br />"'.$query.'"', E_USER_WARNING);
                        $success = false;
                    }
                }
            } else {
                if (!isset($data['uid'])
                    or empty($data['uid'])
                ) {
                    $data['uid'] = '0';
                }
                if (!isset($data['iid'])
                    or empty($data['iid'])
                ) {
                    $data['iid'] = '0';
                }
                $query = 'INSERT INTO '.$this->addDatabasePrefix('log_archive').' SET '.
                    'ip="'.encode(AS_DB, $data['ip']).'", '.
                    'agent="'.encode(AS_DB, $data['agent']).'", '.
                    'timestamp="'.encode(AS_DB, $data['timestamp']).'", '.
                    'request="'.encode(AS_DB, $data['request']).'", '.
                    'method="'.encode(AS_DB, $data['method']).'", '.
                    'uid="'.encode(AS_DB, $data['uid']).'", '.
                    'ulogin="'.encode(AS_DB, $data['ulogin']).'", '.
                    'cid="'.encode(AS_DB, $data['cid']).'", '.
                    'module="'.encode(AS_DB, $data['module']).'", '.
                    'fct="'.encode(AS_DB, $data['fct']).'", '.
                    'param="'.encode(AS_DB, $data['param']).'", '.
                    'iid="'.encode(AS_DB, $data['iid']).'", '.
                    'queries="'.encode(AS_DB, $data['queries']).'", '.
                    'time="'.encode(AS_DB, $data['time']).'"';
                // perform query
                $result = $this->_db_connector->performQuery($query);
                if (!isset($result)) {
                    trigger_error('Problems log_archive from query:<br />"'.$query.'"', E_USER_WARNING);
                    $success = false;
                } else {
                    $success = true;
                }
            }
        }

        return $success;
    }

    public function deleteByContextArray($array)
    {
        $retour = false;
        $query = 'DELETE FROM '.$this->addDatabasePrefix('log_archive').' WHERE 1';
        if (!empty($array)
            and (is_countable($array) ? count($array) : 0) > 0
        ) {
            $id_string = implode(',', $array);
            $query .= ' AND cid NOT IN ('.encode(AS_DB, $id_string).')';
        }

        $datetime = getCurrentDateTimeMinusDaysInMySQL(50);
        $query .= ' AND timestamp < "'.$datetime.'"';

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems at logs from query:<br />"'.$query.'"', E_USER_WARNING);
        } else {
            $retour = $result;
        }

        return $retour;
    }
}
