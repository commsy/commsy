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

use App\Security\Authorization\Voter\FileVoter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Integration\Security\Voter\Concerns\BootsVoter;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Characterization tests for FileVoter (FILE_DOWNLOAD).
 *
 * The voter delegates to:
 *   - $fileItem->maySee($currentUser) — visibility of the linked item
 *   - $fileItem->mayExternalViewerSeeLinkedItem($username) — external viewer table
 *
 * The full happy/sad paths are characterized via ItemVoter::SEE
 * (testExternalViewerCanSeeItemInPrivateRoom etc.) since FileVoter is a
 * thin wrapper around the same legacy machinery.
 *
 * What we pin here is the Voter's edge guard: a non-existent file id leads
 * to a LogicException because the voter assumes the lookup succeeds. The
 * Phase 2 FilePermissionChecker should harden this.
 *
 * Full file-creation integration (creating a cs_file_item linked to a
 * Material item, with portal_id, files_id UUID, item_link_file rows) is
 * deferred — needs a dedicated factory that wires the legacy file pipeline.
 */
#[Group('permission-refactor')]
#[WithStory(AccountStory::class)]
final class FileVoterTest extends KernelTestCase
{
    use BootsVoter;

    public function testNonExistentFileIdRaisesLogicException(): void
    {
        $this->loginAs($this->portalAccount);

        $this->expectException(\LogicException::class);
        $this->authChecker->isGranted(FileVoter::DOWNLOAD, 999_999_999);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->bootVoterContext(AccountStory::get('account'));
    }
}
