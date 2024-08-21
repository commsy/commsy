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

namespace App\Security\Oidc\Response;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class JWKSet
{
    /** @var JWK[] */
    #[SerializedName('keys')]
    private array $keys;

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function setKeys(array $keys): JWKSet
    {
        $this->keys = $keys;
        return $this;
    }
}
