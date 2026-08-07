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

namespace Tests\Unit\Security\Permission\Dispatcher;

use App\Entity\Announcement;
use App\Entity\Discussionarticles;
use App\Entity\Materials;
use App\Entity\User;
use App\Item\TypedEntityResolver;
use App\Rubric\RubricPermissionOverride;
use App\Rubric\RubricType;
use App\Security\Permission\Checker\ItemEditChecker;
use App\Security\Permission\Dispatcher\ItemEditDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ItemEditDispatcherTest extends TestCase
{
    private ItemEditChecker&MockObject $defaultChecker;
    private TypedEntityResolver&MockObject $typedEntityResolver;

    protected function setUp(): void
    {
        $this->defaultChecker = $this->createMock(ItemEditChecker::class);
        $this->typedEntityResolver = $this->createMock(TypedEntityResolver::class);
    }

    // ---- Discussionarticles tombstone gate

    public function testDeniesEditOnOverwrittenDiscussionarticleBeforeAskingDefault(): void
    {
        $article = new Discussionarticles();
        $article->setPublic(-2);

        $this->defaultChecker->expects(self::never())->method('canEdit');

        $dispatcher = $this->dispatcher();

        self::assertFalse($dispatcher->canEdit(new User(), $article));
    }

    public function testDefersToDefaultForLiveDiscussionarticle(): void
    {
        $article = new Discussionarticles();
        $article->setPublic(0);  // normal state

        $this->defaultChecker
            ->expects(self::once())
            ->method('canEdit')
            ->willReturn(true);

        self::assertTrue($this->dispatcher()->canEdit(new User(), $article));
    }

    // ---- Default fallback

    public function testFallsBackToDefaultCheckerForRegularItem(): void
    {
        $item = new Materials();
        $this->defaultChecker
            ->expects(self::once())
            ->method('canEdit')
            ->with(self::isInstanceOf(User::class), $item)
            ->willReturn(true);

        self::assertTrue($this->dispatcher()->canEdit(new User(), $item));
    }

    // ---- Per-rubric override

    public function testRoutesToRegisteredRubricOverride(): void
    {
        $override = $this->createMock(RubricPermissionOverride::class);
        $override->method('rubricType')->willReturn(RubricType::Announcement);
        $override->expects(self::once())
            ->method('canEdit')
            ->willReturn(true);

        $this->defaultChecker->expects(self::never())->method('canEdit');

        $dispatcher = $this->dispatcher([$override]);

        self::assertTrue($dispatcher->canEdit(new User(), new Announcement()));
    }

    public function testOverrideReturningNullDefersToDefault(): void
    {
        $override = $this->createMock(RubricPermissionOverride::class);
        $override->method('rubricType')->willReturn(RubricType::Announcement);
        $override->method('canEdit')->willReturn(null);  // defer

        $this->defaultChecker
            ->expects(self::once())
            ->method('canEdit')
            ->willReturn(false);

        self::assertFalse($this->dispatcher([$override])->canEdit(new User(), new Announcement()));
    }

    // ---- helpers

    /** @param array<RubricPermissionOverride> $overrides */
    private function dispatcher(array $overrides = []): ItemEditDispatcher
    {
        return new ItemEditDispatcher(
            $this->defaultChecker,
            $this->typedEntityResolver,
            $overrides,
        );
    }
}
