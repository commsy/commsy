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

final class AccessTokenResponse
{
    #[SerializedName('access_token')]
    private string $accessToken;

    #[SerializedName('token_type')]
    private string $tokenType;

    #[SerializedName('refresh_token')]
    private string $refreshToken;

    #[SerializedName('expires_in')]
    private int $expiresIn;

    #[SerializedName('id_token')]
    private string $idToken;

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(int $expiresIn): AccessTokenResponse
    {
        $this->expiresIn = $expiresIn;
        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): AccessTokenResponse
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function setTokenType(string $tokenType): AccessTokenResponse
    {
        $this->tokenType = $tokenType;
        return $this;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): AccessTokenResponse
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getIdToken(): string
    {
        return $this->idToken;
    }

    public function setIdToken(string $idToken): AccessTokenResponse
    {
        $this->idToken = $idToken;
        return $this;
    }
}
