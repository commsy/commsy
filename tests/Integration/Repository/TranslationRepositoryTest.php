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

namespace Tests\Integration\Repository;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\TranslationFactory;

final class TranslationRepositoryTest extends KernelTestCase
{
    private TranslationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(TranslationRepository::class);
    }

    public function testFindOneByContextAndKeyReturnsMatchingRow(): void
    {
        $tx = TranslationFactory::createOne([
            'contextId' => 12345,
            'translationKey' => 'GREETING',
            'translationDe' => 'Hallo',
            'translationEn' => 'Hello',
        ]);

        $found = $this->repository->findOneByContextAndKey(12345, 'GREETING');

        self::assertInstanceOf(Translation::class, $found);
        self::assertSame($tx->getId(), $found->getId());
    }

    public function testFindOneByContextAndKeyReturnsNullForUnknownKey(): void
    {
        TranslationFactory::createOne([
            'contextId' => 12346,
            'translationKey' => 'GREETING',
            'translationDe' => 'Hallo',
            'translationEn' => 'Hello',
        ]);

        self::assertNull(
            $this->repository->findOneByContextAndKey(12346, 'NOT_A_KEY'),
        );
    }

    public function testFindOneByContextAndKeyReturnsNullForOtherContext(): void
    {
        TranslationFactory::createOne([
            'contextId' => 12347,
            'translationKey' => 'GREETING',
            'translationDe' => 'Hallo',
            'translationEn' => 'Hello',
        ]);

        self::assertNull(
            $this->repository->findOneByContextAndKey(99999, 'GREETING'),
        );
    }
}
