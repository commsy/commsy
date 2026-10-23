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

namespace App\Room\Vocabulary;

use App\Entity\License;
use App\Entity\Room;
use App\Repository\LicenseRepository;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Licences a room defines for its materials, in the order the room put them
 * in, which is the order the material form offers them.
 */
#[AsTaggedItem(priority: 20)]
final readonly class LicenseVocabulary implements VocabularyKind
{
    public function __construct(private LicenseRepository $licenses)
    {
    }

    #[Override]
    public function field(): string
    {
        return 'licenses';
    }

    #[Override]
    public function termClass(): string
    {
        return RoomLicense::class;
    }

    #[Override]
    public function description(): string
    {
        return 'The room\'s licences; materials point at them by licenseId.';
    }

    /**
     * @return list<RoomLicense>
     */
    #[Override]
    public function of(Room $room): array
    {
        return array_map(
            static fn (License $license): RoomLicense => new RoomLicense(
                (int) $license->getId(),
                (string) $license->getTitle(),
                $license->getContent(),
            ),
            $this->licenses->findByContextOrderByPosition($room->getItemId())
        );
    }
}
