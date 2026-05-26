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

declare(strict_types=1);

namespace Tests\Integration\Concerns;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Pushes a Request with an in-memory Session onto the RequestStack so
 * code paths that read from Session — most notably Symfony forms via
 * SessionTokenStorage for the CSRF token — work in a KernelTestCase
 * scenario that does not go through the HTTP layer.
 *
 * Uses MockArraySessionStorage per the Symfony test docs' guidance for
 * non-persisting unit/integration scenarios. Switch to
 * MockFileSessionStorage in tests that need cross-request persistence
 * (functional flows).
 */
trait PrimesSession
{
    protected function primeSession(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get(RequestStack::class)->push($request);
    }
}
