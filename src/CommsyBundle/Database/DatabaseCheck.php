<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:16
 */

namespace CommsyBundle\Database;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface DatabaseCheck
 * @package CommsyBundle\Database
 */
interface DatabaseCheck
{
    public function getPriority();

    /**
     * @return boolean
     */
    public function check(SymfonyStyle $io);

    /**
     * @return boolean
     */
    public function resolve(SymfonyStyle $io);
}