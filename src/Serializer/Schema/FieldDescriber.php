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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Describes what a normalizer puts into the output of a class beyond the
 * fields the class itself declares, so the schema of that output can be
 * generated rather than written by hand.
 *
 * Implemented by the normalizer that produces the fields: the description
 * stands next to the code it describes, and a field that changes in one place
 * cannot silently stay the same in the other.
 */
#[AutoconfigureTag('app.serializer.field_describer')]
interface FieldDescriber
{
    /**
     * What this adds to the normalized form of $class, or null if it leaves
     * that class alone.
     *
     * @param class-string $class
     */
    public function describe(string $class, SchemaReferences $references): ?FieldDescription;
}
