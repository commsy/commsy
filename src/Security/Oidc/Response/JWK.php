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

final class JWK
{
    #[SerializedName('kty')]
    private string $kty;

    #[SerializedName('use')]
    private string $use;

    #[SerializedName('key_ops')]
    private string $keyOps;

    #[SerializedName('alg')]
    private string $alg;

    #[SerializedName('kid')]
    private string $kid;

    #[SerializedName('n')]
    private string $n;

    #[SerializedName('e')]
    private string $e;

    #[SerializedName('d')]
    private string $d;


    public function getKty(): string
    {
        return $this->kty;
    }

    public function setKty(string $kty): JWK
    {
        $this->kty = $kty;
        return $this;
    }

    public function getUse(): string
    {
        return $this->use;
    }

    public function setUse(string $use): JWK
    {
        $this->use = $use;
        return $this;
    }

    public function getKeyOps(): string
    {
        return $this->keyOps;
    }

    public function setKeyOps(string $keyOps): JWK
    {
        $this->keyOps = $keyOps;
        return $this;
    }

    public function getAlg(): string
    {
        return $this->alg;
    }

    public function setAlg(string $alg): JWK
    {
        $this->alg = $alg;
        return $this;
    }

    public function getKid(): string
    {
        return $this->kid;
    }

    public function setKid(string $kid): JWK
    {
        $this->kid = $kid;
        return $this;
    }

    public function getN(): string
    {
        return $this->n;
    }

    public function setN(string $n): JWK
    {
        $this->n = $n;
        return $this;
    }

    public function getE(): string
    {
        return $this->e;
    }

    public function setE(string $e): JWK
    {
        $this->e = $e;
        return $this;
    }

    public function getD(): string
    {
        return $this->d;
    }

    public function setD(string $d): JWK
    {
        $this->d = $d;
        return $this;
    }
}
