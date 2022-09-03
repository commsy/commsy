<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\Symfony;

class SymfonyHelper extends Module
{
    public function replaceServiceWithMock(string $serviceClassName, ?object $mock)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        $container = $symfony->_getContainer();
        $container->set($serviceClassName, $mock);
//        $symfony->persistService($serviceClassName);
    }
}
