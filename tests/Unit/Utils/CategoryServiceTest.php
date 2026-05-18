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

namespace Tests\Unit\Utils;

use App\Entity\User;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use App\Tag\TagDeleter;
use App\Utils\CategoryService;
use cs_environment;
use PHPUnit\Framework\TestCase;

/**
 * Pins that {@see CategoryService::removeTag()} delegates to {@see TagDeleter::softDelete()}
 * with the current user as deleter; the cascade itself lives in {@see \Tests\Integration\Tag\TagDeleterTest}.
 *
 * Harness updated for Welle A: the deleter id now comes from
 * {@see CurrentUserResolver::getUser()} (Doctrine), not the legacy
 * current user item. The assertions (delegates to TagDeleter with the
 * deleter id; falls back to 0) are unchanged — that is the proof the
 * Welle A migration of this call site was behaviour-neutral.
 *
 * addTag/updateTag/combineTags intentionally uncovered — they exercise legacy
 * cs_tag_manager flows that would require a full container boot; real coverage
 * lives against the legacy manager.
 */
final class CategoryServiceTest extends TestCase
{
    public function testRemoveTagDelegatesToTagDeleterWithCurrentUserAsDeleter(): void
    {
        $tagId = 4711;
        $roomId = 42;
        $userId = 77;

        $legacy = $this->createMock(cs_environment::class);
        $legacy->expects(self::once())
            ->method('setCurrentContextID')
            ->with($roomId);

        $legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $legacyEnvironment->method('getEnvironment')->willReturn($legacy);

        $user = $this->createMock(User::class);
        $user->method('getItemId')->willReturn($userId);
        $currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $currentUserResolver->method('getUser')->willReturn($user);

        $tagDeleter = $this->createMock(TagDeleter::class);
        $tagDeleter->expects(self::once())
            ->method('softDelete')
            ->with($tagId, $userId);

        $service = new CategoryService($legacyEnvironment, $tagDeleter, $currentUserResolver);
        $service->removeTag((string) $tagId, $roomId);
    }

    public function testRemoveTagFallsBackToZeroDeleterIdWhenCurrentUserHasNoItemId(): void
    {
        $tagId = 100;
        $roomId = 9;

        $legacy = $this->createMock(cs_environment::class);
        $legacy->method('setCurrentContextID');

        $legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $legacyEnvironment->method('getEnvironment')->willReturn($legacy);

        // No current user (guest / no membership) -> deleter id falls back to 0.
        $currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $currentUserResolver->method('getUser')->willReturn(null);

        $tagDeleter = $this->createMock(TagDeleter::class);
        $tagDeleter->expects(self::once())
            ->method('softDelete')
            ->with($tagId, 0);

        $service = new CategoryService($legacyEnvironment, $tagDeleter, $currentUserResolver);
        $service->removeTag($tagId, $roomId);
    }
}
