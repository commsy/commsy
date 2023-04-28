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

class RubricVoter extends Voter
{
    final public const ANNOUNCEMENT = 'RUBRIC_ANNOUNCEMENT';
    final public const DATE = 'RUBRIC_DATE';
    final public const DISCUSSION = 'RUBRIC_DISCUSSION';
    final public const GROUP = 'RUBRIC_GROUP';
    final public const MATERIAL = 'RUBRIC_MATERIAL';
    final public const TOPIC = 'RUBRIC_TOPIC';
    final public const USER = 'RUBRIC_USER';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [
            self::ANNOUNCEMENT,
            self::DATE,
            self::DISCUSSION,
            self::GROUP,
            self::MATERIAL,
            self::TOPIC,
            self::USER
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        return match ($attribute) {
            self::ANNOUNCEMENT => $this->canView($roomItem, $currentUser, 'announcement'),
            self::DATE => $this->canView($roomItem, $currentUser, 'date'),
            self::DISCUSSION => $this->canView($roomItem, $currentUser, 'discussion'),
            self::GROUP => $this->canView($roomItem, $currentUser, 'group'),
            self::MATERIAL => $this->canView($roomItem, $currentUser, 'material'),
            self::TOPIC => $this->canView($roomItem, $currentUser, 'topic'),
            self::USER => $this->canView($roomItem, $currentUser, 'user'),
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
