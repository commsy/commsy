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

namespace Tests\Unit\Entity;

use App\Entity\Account;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testEmptyDisplayName(): void
    {
        $account = new Account();
        $account->setUsername('username');
        $this->assertSame('username', $account->getDisplayName());

        $account->setDisplayName(null);
        $this->assertSame('username', $account->getDisplayName());
    }

    public function testDisplayName(): void
    {
        $account = new Account();
        $account->setUsername('username');
        $account->setDisplayName('displayname');

        $this->assertSame('displayname', $account->getDisplayName());
    }
}
