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

namespace App\Security\Authorization\Voter;

use App\Services\LegacyEnvironment;
use cs_environment;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DateVoter extends Voter
{
    final public const EDIT = 'edit';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $date = $subject;

        return match ($attribute) {
            self::EDIT => $this->canEdit($date),
            default => throw new LogicException('The code no show.'),
        };
    }

    private function canEdit($date)
    {
        return $date->isPublic() || $date->getCreatorID() === $this->legacyEnvironment->getCurrentUserID();
    }
}
