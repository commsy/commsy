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

namespace App\Room;

use App\Legacy\ItemTypeMapper;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The rubrics a room offers, in its order, each shown or hidden.
 *
 * A room keeps them in its extras as `HOMECONF`, a comma-separated list such as
 * `material_show,todo_hide`; a rubric that is off is left out. A room that has
 * none offers the default of its type.
 */
#[Exclude]
final readonly class RubricConfiguration
{
    /**
     * @param array<string, RubricVisibility> $visibilities by rubric, in the room's order
     */
    public function __construct(private array $visibilities)
    {
    }

    /**
     * Reads what a room has stored the way legacy always has: an entry offers
     * its rubric whatever follows the underscore. Since 2023 that is `show` or
     * `hide`; an older spelling counts as shown, an older module name such as
     * `topics` as today's.
     */
    public static function fromString(string $configuration): self
    {
        $visibilities = [];
        foreach (explode(',', $configuration) as $entry) {
            [$rubric, $visibility] = array_pad(explode('_', $entry, 2), 2, '');
            if ('' === $rubric) {
                continue;
            }

            $visibilities[ItemTypeMapper::fromModule($rubric)] ??= RubricVisibility::tryFrom($visibility)
                ?? RubricVisibility::Show;
        }

        return new self($visibilities);
    }

    /**
     * What a room of the given type offers without settings of its own. A
     * context that is no room offers nothing.
     */
    public static function defaultFor(?RoomType $type): self
    {
        return new self(array_fill_keys($type?->defaultRubrics() ?? [], RubricVisibility::Show));
    }

    /**
     * Reads a configuration about to be stored. Unlike reading what is stored,
     * this refuses any entry that is not a rubric the room type offers, shown
     * or hidden.
     *
     * @throws InvalidArgumentException
     */
    public static function validated(string $configuration, ?RoomType $type): self
    {
        $offered = $type?->defaultRubrics() ?? [];
        foreach ('' === $configuration ? [] : explode(',', $configuration) as $entry) {
            [$rubric, $visibility] = array_pad(explode('_', $entry, 2), 2, '');
            if (!in_array($rubric, $offered, true) || null === RubricVisibility::tryFrom($visibility)) {
                throw new InvalidArgumentException(sprintf('Invalid rubric configuration "%s".', $configuration));
            }
        }

        return self::fromString($configuration);
    }

    /**
     * @return list<string>
     */
    public function rubrics(): array
    {
        return array_map(strval(...), array_keys($this->visibilities));
    }

    /**
     * @return array<string, RubricVisibility>
     */
    public function visibilities(): array
    {
        return $this->visibilities;
    }

    public function offers(string $rubric): bool
    {
        return isset($this->visibilities[$rubric]);
    }

    public function visibilityOf(string $rubric): ?RubricVisibility
    {
        return $this->visibilities[$rubric] ?? null;
    }

    /**
     * The form `HOMECONF` stores.
     */
    public function toString(): string
    {
        $entries = [];
        foreach ($this->visibilities as $rubric => $visibility) {
            $entries[] = $rubric.'_'.$visibility->value;
        }

        return implode(',', $entries);
    }
}
