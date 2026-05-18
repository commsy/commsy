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

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Schritt 5 — containment + success metric for the CurrentUserResolver
 * refactor ("Schloss 1 of 3").
 *
 * The legacy current-user seam `getCurrentUserItem()` was driven down
 * from 72 src/ files to the list below. This is NOT meant to reach 0:
 * the remainder is either the terminal setModificator/setCreatorItem
 * family (dies with the cs_*_item classes — Schloss 3) or per-room
 * lookups deferred to Schloss 2/Welle E. Each entry is legacy-internal
 * or explicitly tracked; new application code must use
 * {@see \App\Services\CurrentUserResolver} instead.
 *
 * Strict equality is intentional — it is the ratchet AND the live
 * metric:
 *   - a NEW file using getCurrentUserItem() fails the test (regrowth
 *     blocked; use CurrentUserResolver),
 *   - removing the last usage from an allowlisted file also fails
 *     (the seam shrank — trim the list so the metric stays honest;
 *     the diff is the progress record).
 */
final class CurrentUserSeamContainmentTest extends TestCase
{
    /**
     * src/ files still allowed to reference the legacy
     * getCurrentUserItem() seam (relative to src/). Baseline after
     * Schloss 1: 56.
     *
     * @var string[]
     */
    private const ALLOWLIST = [
        'Action/Copy/InsertUserroomAction.php',
        'Controller/AnnouncementController.php',
        'Controller/ContextController.php',
        'Controller/DashboardController.php',
        'Controller/DateController.php',
        'Controller/DiscussionController.php',
        'Controller/EtherpadController.php',
        'Controller/GroupController.php',
        'Controller/ICalController.php',
        'Controller/ItemController.php',
        'Controller/MaterialController.php',
        'Controller/PortalSettingsController.php',
        'Controller/ProfileController.php',
        'Controller/ProjectController.php',
        'Controller/RoomController.php',
        'Controller/SearchController.php',
        'Controller/TodoController.php',
        'Controller/TopicController.php',
        'Controller/TouController.php',
        'Controller/UploadController.php',
        'Controller/UserController.php',
        'EventSubscriber/ItemSubscriber.php',
        'EventSubscriber/RoomListFilterConditionSubscriber.php',
        'EventSubscriber/SecuritySubscriber.php',
        'EventSubscriber/TermsOfUseSubscriber.php',
        'Facade/UserCreatorFacade.php',
        'Form/DataTransformer/AdditionalSettingsTransformer.php',
        'Form/DataTransformer/UserTransformer.php',
        'Form/Type/Context/ProjectType.php',
        'Form/Type/GeneralSettingsType.php',
        'Legacy/LegacySoftDeleteBridge.php',
        'Mail/Messages/RoomModerationMessage.php',
        'Menu/MenuBuilder.php',
        'Room/RoomManager.php',
        'Search/FilterConditions/MultipleContextFilterCondition.php',
        'Search/FilterConditions/ReadStatusFilterCondition.php',
        'Search/FilterConditions/RoomFilterCondition.php',
        'Security/Authorization/Voter/CalendarsVoter.php',
        'Security/Authorization/Voter/FileVoter.php',
        'Security/Authorization/Voter/HashtagVoter.php',
        'Security/Authorization/Voter/ItemVoter.php',
        'Security/Authorization/Voter/RubricVoter.php',
        'Security/Authorization/Voter/UserVoter.php',
        'Twig/Components/FileList/FileList.php',
        'Utils/AccountMail.php',
        'Utils/CategoryService.php',
        'Utils/DownloadService.php',
        'Utils/ItemService.php',
        'Utils/LabelService.php',
        'Utils/MailAssistant.php',
        'Utils/ReaderService.php',
        'Utils/RoomService.php',
        'Utils/TimePulsesService.php',
        'Utils/UserService.php',
        'Validator/Constraints/ModeratorAccountDeleteConstraintValidator.php',
        'Validator/Constraints/UniqueModeratorConstraintValidator.php',
    ];

    public function testLegacyCurrentUserSeamDoesNotRegrow(): void
    {
        $srcDir = \dirname(__DIR__, 3).'/src';
        $actual = [];

        /** @var \SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            if (str_contains((string) file_get_contents($file->getPathname()), 'getCurrentUserItem(')) {
                $actual[] = str_replace($srcDir.'/', '', $file->getPathname());
            }
        }

        sort($actual);
        $allowlist = self::ALLOWLIST;
        sort($allowlist);

        $newOffenders = array_diff($actual, $allowlist);
        self::assertSame(
            [],
            array_values($newOffenders),
            "New src/ file(s) reference the legacy getCurrentUserItem() seam.\n"
            ."Use App\\Services\\CurrentUserResolver instead — do not regrow the seam:\n  "
            .implode("\n  ", $newOffenders)
        );

        $shrunk = array_diff($allowlist, $actual);
        self::assertSame(
            [],
            array_values($shrunk),
            "The seam shrank (good!) — these allowlisted files no longer use\n"
            ."getCurrentUserItem(). Remove them from ALLOWLIST so the metric\n"
            ."stays honest (the diff records the progress):\n  "
            .implode("\n  ", $shrunk)
        );
    }
}
