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
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RubricVoter extends Voter
{
    public const RUBRIC_SEE = 'RUBRIC_SEE';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $object)
    {
        return in_array($attribute, [self::RUBRIC_SEE]);
    }

    protected function voteOnAttribute($attribute, $rubricName, TokenInterface $token)
    {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        return match ($attribute) {
            self::RUBRIC_SEE => $this->canView($roomItem, $currentUser, $rubricName),
            default => throw new LogicException('This code should not be reached!'),
        };
    }

    private function canView($roomItem, $currentUser, $rubric)
    {
        if ($roomItem->isDeleted()) {
            return false;
        }
        if ($roomItem->isPrivateRoom() && in_array($rubric, ['material', 'date', 'discussion', 'announcement', 'todo'])) {
            return true;
        }
        if ('user' == $rubric && $currentUser->isModerator()) {
            return true;
        }

        return in_array($rubric, $roomItem->getAvailableRubrics());
    }
}
