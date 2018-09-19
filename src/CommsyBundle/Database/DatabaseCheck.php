<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 15.02.18
 * Time: 18:16
 */

namespace CommsyBundle\Database;

use CommsyBundle\Database\Resolve\ResolutionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface DatabaseCheck
 * @package CommsyBundle\Database
 */
interface DatabaseCheck
{
    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param SymfonyStyle $io
     * @return DatabaseProblem[]
     */
    public function findProblems(SymfonyStyle $io, int $limit);


    /**
     * @return ResolutionInterface[]
     */
    public function getResolutionStrategies();
}