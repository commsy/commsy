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

namespace App\Metrics\Data;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class CachedLogin
{
    private DateTimeImmutable $cached;

    public function __construct(
        private readonly int $accountId,
        private readonly string $portalTitle
    ) {
        $this->cached = new DateTimeImmutable();
    }

    public function getCached(): DateTimeImmutable
    {
        return $this->cached;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getPortalTitle(): string
    {
        return $this->portalTitle;
    }
}
