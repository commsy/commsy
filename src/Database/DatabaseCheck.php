<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:16
 */

namespace App\Database;

use App\Database\Resolve\ResolutionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface DatabaseCheck
 * @package App\Database
 */
interface DatabaseCheck
{
    /**
     * @return int
     */
    public function getPriority();

    public function resolve(SymfonyStyle $io): bool;
}