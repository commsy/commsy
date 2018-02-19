<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:15
 */

namespace CommsyBundle\Database;


class DatabaseChecks
{
    /**
     * @var DatabaseCheck[]
     */
    private $checks;

    public function __construct()
    {
        $this->checks = [];
    }

    public function addCheck(DatabaseCheck $check)
    {
        $this->checks[] = $check;
    }

    public function runChecks()
    {
        foreach ($this->checks as $check) {
            $check->check();
        }
    }
}