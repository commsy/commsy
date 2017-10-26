<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 02.09.17
 * Time: 19:02
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;

use CommSy\Command\MovePrivateCommand;


$application = new Application();
$application->add(new MovePrivateCommand());
$application->run();