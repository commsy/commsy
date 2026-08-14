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

namespace App\Etherpad;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Where a material's pad lives: its Etherpad group and the pad id within it.
 *
 * The group is per material, not per room. An Etherpad session is issued for
 * a whole group, while the right to open the editor is checked per material —
 * a group covering the room would hand out access to every material's pad in
 * it, well beyond what was checked.
 *
 * Both values are derived, never stored, so they cannot drift from the item
 * they belong to.
 */
#[Exclude]
final readonly class MaterialPad
{
    /**
     * The group already names the material, so the pad within it needs no
     * further distinction.
     */
    private const PAD_NAME = 'description';

    private function __construct(
        public string $groupId,
        public string $padId,
    ) {
    }

    public static function locate(EtherpadClient $client, int $materialId): self
    {
        $groupId = $client->createGroupIfNotExistsFor(self::groupMapper($materialId));

        return new self($groupId, EtherpadClient::padId($groupId, self::PAD_NAME));
    }

    /**
     * Prefixed so these groups stay distinguishable from the room-scoped ones
     * this replaced.
     */
    public static function groupMapper(int $materialId): string
    {
        return 'material-'.$materialId;
    }

    public static function padName(): string
    {
        return self::PAD_NAME;
    }
}
