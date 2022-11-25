<?php

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;

class Functional extends Module
{
    public function grabSymfony(): Module\Symfony
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getModule('Symfony');
    }
}
