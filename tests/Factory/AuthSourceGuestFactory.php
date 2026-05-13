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

use App\Entity\AuthSourceGuest;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<AuthSourceGuest>
 *
 * The presence of an *enabled* AuthSourceGuest on a Portal is what makes
 * `PortalProxy::isOpenForGuests()` return true — it's the configuration
 * surface for "this portal accepts unauthenticated guest visits".
 */
final class AuthSourceGuestFactory extends PersistentObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return AuthSourceGuest::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => 'guest',
            'enabled' => true,
            'default' => false,
            'createRoom' => false,
            'description' => self::faker()->text(),
        ];
    }
}
