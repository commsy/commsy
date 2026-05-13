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

namespace Tests\Unit\Security\Permission\Checker;

use App\Entity\ExternalViewer;
use App\Security\Permission\Checker\ExternalViewerChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ExternalViewerCheckerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $repository;
    private ExternalViewerChecker $checker;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')
            ->with(ExternalViewer::class)
            ->willReturn($this->repository);
        $this->checker = new ExternalViewerChecker($this->entityManager);
    }

    public function testReturnsTrueWhenRowExists(): void
    {
        $this->repository->expects(self::once())
            ->method('find')
            ->with(['itemId' => 42, 'userId' => 'alice'])
            ->willReturn(new ExternalViewer());

        self::assertTrue($this->checker->isViewerOf(42, 'alice'));
    }

    public function testReturnsFalseWhenRowMissing(): void
    {
        $this->repository->method('find')->willReturn(null);

        self::assertFalse($this->checker->isViewerOf(42, 'stranger'));
    }
}
