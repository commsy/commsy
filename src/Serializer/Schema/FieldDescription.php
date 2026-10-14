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

namespace App\Serializer\Schema;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The fields a normalizer adds to a class's output, as JSON Schema.
 */
#[Exclude]
final readonly class FieldDescription
{
    /**
     * @param array<string, array<string, mixed>> $fields   JSON Schema of each field, keyed by its name
     * @param list<string>                        $optional fields that are not always present
     * @param bool                                $replaces whether these fields take the place of the class's own
     */
    public function __construct(
        public array $fields,
        public array $optional = [],
        public bool $replaces = false,
    ) {
    }
}
