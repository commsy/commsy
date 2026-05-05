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

namespace Tests\Integration\Security\Voter;

use App\Entity\Portal;
use App\Security\Authorization\Voter\PortalModeratorVoter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for PortalModeratorVoter (PORTAL_MODERATOR with a
 * Portal subject — distinct from UserVoter::PORTAL_MODERATOR which has no
 * subject and reads currentUserItem).
 *
 *   true iff: user is root,
 *         OR (currentUserItem.status === 3 AND portal.deletionDate IS NULL
 *             AND currentUserItem.contextID === portal.id)
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class PortalModeratorVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testRootShortCircuit(): void
    {
        $portal = $this->portalAccount->getPortal();
        $rootAccount = $this->createPortalAccount('root');
        $this->actAsRoot($rootAccount);

        self::assertTrue(
            $this->authChecker->isGranted(PortalModeratorVoter::PORTAL_MODERATOR, $portal),
        );
    }

    public function testPortalModeratorOfMatchingPortalIsGranted(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        self::assertTrue(
            $this->authChecker->isGranted(
                PortalModeratorVoter::PORTAL_MODERATOR,
                $this->portalAccount->getPortal(),
            ),
        );
    }

    public function testRegularUserIsNotGranted(): void
    {
        $this->loginAs($this->portalAccount);

        self::assertFalse(
            $this->authChecker->isGranted(
                PortalModeratorVoter::PORTAL_MODERATOR,
                $this->portalAccount->getPortal(),
            ),
        );
    }

    /**
     * Characterization of an overlap: BOTH PortalModeratorVoter (which
     * checks subject->getDeletionDate()) AND UserVoter (which doesn't
     * inspect the subject Portal at all and just checks the legacy user's
     * status === 3) register for the PORTAL_MODERATOR attribute. With
     * Symfony's default "affirmative" strategy any voter granting wins —
     * so the deleted-portal guard in PortalModeratorVoter is *effectively
     * dead code*: even when the Portal has a deletion_date, UserVoter
     * still grants because the cs_user_item is still status=3.
     *
     * Phase 2 should consolidate these two voters; until then we pin the
     * surprising "deleted portal still grants PORTAL_MODERATOR" outcome
     * so any refactor change is visible.
     */
    public function testDeletedPortalStillGrantsBecauseUserVoterAlsoVotes(): void
    {
        $this->promoteToPortalModerator($this->portalAccount);
        $this->loginAs($this->portalAccount);

        $portal = $this->portalAccount->getPortal();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $portal->setDeletionDate(new \DateTime());
        $em->flush();

        self::assertTrue(
            $this->authChecker->isGranted(
                PortalModeratorVoter::PORTAL_MODERATOR,
                $portal,
            ),
            'Surprising: UserVoter::PORTAL_MODERATOR overrules PortalModeratorVoter\'s deleted-portal block',
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
