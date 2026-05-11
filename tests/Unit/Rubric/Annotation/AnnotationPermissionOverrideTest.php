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

namespace Tests\Unit\Rubric\Annotation;

use App\Entity\Annotations;
use App\Entity\Materials;
use App\Entity\User;
use App\Rubric\Annotation\AnnotationPermissionOverride;
use App\Rubric\RubricType;
use App\Security\Permission\Checker\ExternalViewerChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class AnnotationPermissionOverrideTest extends TestCase
{
    private ExternalViewerChecker&MockObject $externalViewerChecker;
    private AnnotationPermissionOverride $override;

    protected function setUp(): void
    {
        $this->externalViewerChecker = $this->createMock(ExternalViewerChecker::class);
        $this->override = new AnnotationPermissionOverride($this->externalViewerChecker);
    }

    public function testRubricTypeIsAnnotation(): void
    {
        self::assertSame(RubricType::Annotation, $this->override->rubricType());
    }

    public function testGrantsWhenActorIsExternalViewerOfLinkedItem(): void
    {
        $actor = $this->user(itemId: 5, userId: 'alice');
        $annotation = $this->annotation(linkedItemId: 77);

        $this->externalViewerChecker
            ->expects(self::once())
            ->method('isViewerOf')
            ->with(77, 'alice')
            ->willReturn(true);

        self::assertTrue($this->override->canEdit($actor, $annotation));
    }

    public function testDefersWhenActorIsNotExternalViewer(): void
    {
        $actor = $this->user(itemId: 5, userId: 'alice');
        $annotation = $this->annotation(linkedItemId: 77);

        $this->externalViewerChecker->method('isViewerOf')->willReturn(false);

        self::assertNull(
            $this->override->canEdit($actor, $annotation),
            'When external-viewer fails, defer to default ItemEditChecker',
        );
    }

    public function testDefersWhenAnnotationHasNoLinkedItem(): void
    {
        $actor = $this->user(itemId: 5, userId: 'alice');
        $annotation = $this->annotation(linkedItemId: 0);

        $this->externalViewerChecker->expects(self::never())->method('isViewerOf');

        self::assertNull($this->override->canEdit($actor, $annotation));
    }

    public function testDefersForNonAnnotationItem(): void
    {
        $actor = $this->user(itemId: 5, userId: 'alice');
        // Dispatcher routes by RubricType; receiving a wrong type
        // indicates a wiring bug but the override should defer rather
        // than throw.
        self::assertNull($this->override->canEdit($actor, new Materials()));
    }

    private function user(int $itemId, string $userId): User
    {
        $u = (new User())->setStatus(2);
        $u->itemId = $itemId;
        $u->userId = $userId;
        return $u;
    }

    private function annotation(int $linkedItemId): Annotations
    {
        $a = new Annotations();
        $idProp = new ReflectionProperty(Annotations::class, 'itemId');
        $idProp->setValue($a, 100);
        $linkedProp = new ReflectionProperty(Annotations::class, 'linkedItemId');
        $linkedProp->setValue($a, $linkedItemId);
        return $a;
    }
}
