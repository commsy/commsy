<?php

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use App\Entity\Account;
use Codeception\Module;
use League\FactoryMuffin\Faker\Facade as Faker;

class Factories extends Module
{
    public function _beforeSuite($settings = [])
    {
        $factory = $this->getModule('DataFactory');
        $em = $this->getModule('Doctrine')->_getEntityManager();

        $factory->_define(Account::class, [
            'firstname' => Faker::firstName(),
            'lastname' => Faker::lastName(),
            'email' => Faker::email(),
            'language' => 'de',
            'plainPassword' => Faker::password(),
            'password' => fn($object) => password_hash((string) $object->getPlainPassword(), PASSWORD_BCRYPT),
        ]);
    }
}
