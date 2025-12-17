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

namespace Tests\Factory;

use App\Entity\AuthSourceOIDC;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<AuthSourceOIDC>
 */
final class AuthSourceOIDCFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return AuthSourceOIDC::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->word(),
            'enabled' => true,
            'default' => true,
            'createRoom' => true,
            'description' => self::faker()->text(),
            'clientIdentifier' => self::faker()->word(),
            'clientSecret' => self::faker()->password(),
            'displaynameMapping' => '$.displayname',
            'emailMapping' => '$.email',
            'firstnameMapping' => '$.firstname',
            'issuer' => self::faker()->url(),
            'lastnameMapping' => '$.lastname',
            'userInfoUrl' => self::faker()->url(),
            'usernameMapping' => '$.username',
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(AuthSourceOIDC $authSourceOIDC): void {})
        ;
    }
}
