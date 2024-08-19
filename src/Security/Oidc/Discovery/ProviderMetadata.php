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

namespace App\Security\Oidc\Discovery;

use Symfony\Component\Serializer\Attribute\SerializedName;

class ProviderMetadata
{
    #[SerializedName('issuer')]
    private string $issuer;

    #[SerializedName('authorization_endpoint')]
    private string $authorizationEndpoint;

    #[SerializedName('token_endpoint')]
    private string $tokenEndpoint;

    #[SerializedName('userinfo_endpoint')]
    private string $userInfoEndpoint;

    #[SerializedName('jwks_uri')]
    private string $jwksUri;

    #[SerializedName('registration_endpoint')]
    private string $registrationEndpoint;

    #[SerializedName('scopes_supported')]
    private array $scopesSupported;

    #[SerializedName('response_types_supported')]
    private array $responseTypesSupported;

    #[SerializedName('response_modes_supported')]
    private array $responseModesSupported;

    #[SerializedName('grant_types_supported')]
    private array $grantTypesSupported;

    #[SerializedName('acr_values_supported')]
    private array $acrValuesSupported;

    #[SerializedName('subject_types_supported')]
    private array $subjectTypesSupported;

    #[SerializedName('id_token_signing_alg_values_supported')]
    private array $idTokenSigningAlgValuesSupported;

    #[SerializedName('id_token_encryption_alg_values_supported')]
    private array $idTokenEncryptionAlgValuesSupported;

    #[SerializedName('id_token_encryption_enc_values_supported')]
    private array $idTokenEncryptionEncValuesSupported;

    #[SerializedName('userinfo_signing_alg_values_supported')]
    private array $userInfoSigningAlgValuesSupported;

    #[SerializedName('userinfo_encryption_alg_values_supported')]
    private array $userInfoEncryptionAlgValuesSupported;

    #[SerializedName('userinfo_encryption_enc_values_supported')]
    private array $userInfoEncryptionEncValuesSupported;

    #[SerializedName('request_object_signing_alg_values_supported')]
    private array $requestObjectSigningAlgValuesSupported;

    #[SerializedName('request_object_encryption_alg_values_supported')]
    private array $requestObjectEncryptionAlgValuesSupported;

    #[SerializedName('request_object_encryption_enc_values_supported')]
    private array $requestObjectEncryptionEncValuesSupported;

    #[SerializedName('token_endpoint_auth_methods_supported')]
    private array $tokenEndpointAuthMethodsSupported;

    #[SerializedName('token_endpoint_auth_signing_alg_values_supported')]
    private array $tokenEndpointAuthSigningAlgValuesSupported;

    #[SerializedName('display_values_supported')]
    private array $displayValuesSupported;

    #[SerializedName('claim_types_supported')]
    private array $claimTypesSupported;

    #[SerializedName('claims_supported')]
    private array $claimsSupported;

    #[SerializedName('service_documentation')]
    private string $serviceDocumentation;

    #[SerializedName('claims_locales_supported')]
    private array $claimsLocalesSupported;

    #[SerializedName('ui_locales_supported')]
    private array $uiLocalesSupported;

    #[SerializedName('claims_parameter_supported')]
    private bool $claimsParameterSupported;

    #[SerializedName('request_parameter_supported')]
    private bool $requestParameterSupported;

    #[SerializedName('request_uri_parameter_supported')]
    private bool $requestUriParameterSupported;

    #[SerializedName('require_request_uri_registration')]
    private bool $requireRequestUriRegistration;

    #[SerializedName('op_policy_uri')]
    private string $opPolicyUri;

    #[SerializedName('op_tos_uri')]
    private string $opTosUri;

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setIssuer(string $issuer): ProviderMetadata
    {
        $this->issuer = $issuer;
        return $this;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorizationEndpoint;
    }

    public function setAuthorizationEndpoint(string $authorizationEndpoint): ProviderMetadata
    {
        $this->authorizationEndpoint = $authorizationEndpoint;
        return $this;
    }

    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    public function setTokenEndpoint(string $tokenEndpoint): ProviderMetadata
    {
        $this->tokenEndpoint = $tokenEndpoint;
        return $this;
    }

    public function getUserInfoEndpoint(): string
    {
        return $this->userInfoEndpoint;
    }

    public function setUserInfoEndpoint(string $userInfoEndpoint): ProviderMetadata
    {
        $this->userInfoEndpoint = $userInfoEndpoint;
        return $this;
    }

    public function getJwksUri(): string
    {
        return $this->jwksUri;
    }

    public function setJwksUri(string $jwksUri): ProviderMetadata
    {
        $this->jwksUri = $jwksUri;
        return $this;
    }

    public function getRegistrationEndpoint(): string
    {
        return $this->registrationEndpoint;
    }

    public function setRegistrationEndpoint(string $registrationEndpoint): ProviderMetadata
    {
        $this->registrationEndpoint = $registrationEndpoint;
        return $this;
    }

    public function getScopesSupported(): array
    {
        return $this->scopesSupported;
    }

    public function setScopesSupported(array $scopesSupported): ProviderMetadata
    {
        $this->scopesSupported = $scopesSupported;
        return $this;
    }

    public function getResponseTypesSupported(): array
    {
        return $this->responseTypesSupported;
    }

    public function setResponseTypesSupported(array $responseTypesSupported): ProviderMetadata
    {
        $this->responseTypesSupported = $responseTypesSupported;
        return $this;
    }

    public function getResponseModesSupported(): array
    {
        return $this->responseModesSupported;
    }

    public function setResponseModesSupported(array $responseModesSupported): ProviderMetadata
    {
        $this->responseModesSupported = $responseModesSupported;
        return $this;
    }

    public function getGrantTypesSupported(): array
    {
        return $this->grantTypesSupported;
    }

    public function setGrantTypesSupported(array $grantTypesSupported): ProviderMetadata
    {
        $this->grantTypesSupported = $grantTypesSupported;
        return $this;
    }

    public function getAcrValuesSupported(): array
    {
        return $this->acrValuesSupported;
    }

    public function setAcrValuesSupported(array $acrValuesSupported): ProviderMetadata
    {
        $this->acrValuesSupported = $acrValuesSupported;
        return $this;
    }

    public function getSubjectTypesSupported(): array
    {
        return $this->subjectTypesSupported;
    }

    public function setSubjectTypesSupported(array $subjectTypesSupported): ProviderMetadata
    {
        $this->subjectTypesSupported = $subjectTypesSupported;
        return $this;
    }

    public function getIdTokenSigningAlgValuesSupported(): array
    {
        return $this->idTokenSigningAlgValuesSupported;
    }

    public function setIdTokenSigningAlgValuesSupported(array $idTokenSigningAlgValuesSupported): ProviderMetadata
    {
        $this->idTokenSigningAlgValuesSupported = $idTokenSigningAlgValuesSupported;
        return $this;
    }

    public function getIdTokenEncryptionAlgValuesSupported(): array
    {
        return $this->idTokenEncryptionAlgValuesSupported;
    }

    public function setIdTokenEncryptionAlgValuesSupported(array $idTokenEncryptionAlgValuesSupported): ProviderMetadata
    {
        $this->idTokenEncryptionAlgValuesSupported = $idTokenEncryptionAlgValuesSupported;
        return $this;
    }

    public function getIdTokenEncryptionEncValuesSupported(): array
    {
        return $this->idTokenEncryptionEncValuesSupported;
    }

    public function setIdTokenEncryptionEncValuesSupported(array $idTokenEncryptionEncValuesSupported): ProviderMetadata
    {
        $this->idTokenEncryptionEncValuesSupported = $idTokenEncryptionEncValuesSupported;
        return $this;
    }

    public function getUserInfoSigningAlgValuesSupported(): array
    {
        return $this->userInfoSigningAlgValuesSupported;
    }

    public function setUserInfoSigningAlgValuesSupported(array $userInfoSigningAlgValuesSupported): ProviderMetadata
    {
        $this->userInfoSigningAlgValuesSupported = $userInfoSigningAlgValuesSupported;
        return $this;
    }

    public function getUserInfoEncryptionAlgValuesSupported(): array
    {
        return $this->userInfoEncryptionAlgValuesSupported;
    }

    public function setUserInfoEncryptionAlgValuesSupported(array $userInfoEncryptionAlgValuesSupported): ProviderMetadata
    {
        $this->userInfoEncryptionAlgValuesSupported = $userInfoEncryptionAlgValuesSupported;
        return $this;
    }

    public function getUserInfoEncryptionEncValuesSupported(): array
    {
        return $this->userInfoEncryptionEncValuesSupported;
    }

    public function setUserInfoEncryptionEncValuesSupported(array $userInfoEncryptionEncValuesSupported): ProviderMetadata
    {
        $this->userInfoEncryptionEncValuesSupported = $userInfoEncryptionEncValuesSupported;
        return $this;
    }

    public function getRequestObjectSigningAlgValuesSupported(): array
    {
        return $this->requestObjectSigningAlgValuesSupported;
    }

    public function setRequestObjectSigningAlgValuesSupported(array $requestObjectSigningAlgValuesSupported): ProviderMetadata
    {
        $this->requestObjectSigningAlgValuesSupported = $requestObjectSigningAlgValuesSupported;
        return $this;
    }

    public function getRequestObjectEncryptionAlgValuesSupported(): array
    {
        return $this->requestObjectEncryptionAlgValuesSupported;
    }

    public function setRequestObjectEncryptionAlgValuesSupported(array $requestObjectEncryptionAlgValuesSupported): ProviderMetadata
    {
        $this->requestObjectEncryptionAlgValuesSupported = $requestObjectEncryptionAlgValuesSupported;
        return $this;
    }

    public function getRequestObjectEncryptionEncValuesSupported(): array
    {
        return $this->requestObjectEncryptionEncValuesSupported;
    }

    public function setRequestObjectEncryptionEncValuesSupported(array $requestObjectEncryptionEncValuesSupported): ProviderMetadata
    {
        $this->requestObjectEncryptionEncValuesSupported = $requestObjectEncryptionEncValuesSupported;
        return $this;
    }

    public function getTokenEndpointAuthMethodsSupported(): array
    {
        return $this->tokenEndpointAuthMethodsSupported;
    }

    public function setTokenEndpointAuthMethodsSupported(array $tokenEndpointAuthMethodsSupported): ProviderMetadata
    {
        $this->tokenEndpointAuthMethodsSupported = $tokenEndpointAuthMethodsSupported;
        return $this;
    }

    public function getTokenEndpointAuthSigningAlgValuesSupported(): array
    {
        return $this->tokenEndpointAuthSigningAlgValuesSupported;
    }

    public function setTokenEndpointAuthSigningAlgValuesSupported(array $tokenEndpointAuthSigningAlgValuesSupported): ProviderMetadata
    {
        $this->tokenEndpointAuthSigningAlgValuesSupported = $tokenEndpointAuthSigningAlgValuesSupported;
        return $this;
    }

    public function getDisplayValuesSupported(): array
    {
        return $this->displayValuesSupported;
    }

    public function setDisplayValuesSupported(array $displayValuesSupported): ProviderMetadata
    {
        $this->displayValuesSupported = $displayValuesSupported;
        return $this;
    }

    public function getClaimTypesSupported(): array
    {
        return $this->claimTypesSupported;
    }

    public function setClaimTypesSupported(array $claimTypesSupported): ProviderMetadata
    {
        $this->claimTypesSupported = $claimTypesSupported;
        return $this;
    }

    public function getClaimsSupported(): array
    {
        return $this->claimsSupported;
    }

    public function setClaimsSupported(array $claimsSupported): ProviderMetadata
    {
        $this->claimsSupported = $claimsSupported;
        return $this;
    }

    public function getServiceDocumentation(): string
    {
        return $this->serviceDocumentation;
    }

    public function setServiceDocumentation(string $serviceDocumentation): ProviderMetadata
    {
        $this->serviceDocumentation = $serviceDocumentation;
        return $this;
    }

    public function getClaimsLocalesSupported(): array
    {
        return $this->claimsLocalesSupported;
    }

    public function setClaimsLocalesSupported(array $claimsLocalesSupported): ProviderMetadata
    {
        $this->claimsLocalesSupported = $claimsLocalesSupported;
        return $this;
    }

    public function getUiLocalesSupported(): array
    {
        return $this->uiLocalesSupported;
    }

    public function setUiLocalesSupported(array $uiLocalesSupported): ProviderMetadata
    {
        $this->uiLocalesSupported = $uiLocalesSupported;
        return $this;
    }

    public function isClaimsParameterSupported(): bool
    {
        return $this->claimsParameterSupported;
    }

    public function setClaimsParameterSupported(bool $claimsParameterSupported): ProviderMetadata
    {
        $this->claimsParameterSupported = $claimsParameterSupported;
        return $this;
    }

    public function isRequestParameterSupported(): bool
    {
        return $this->requestParameterSupported;
    }

    public function setRequestParameterSupported(bool $requestParameterSupported): ProviderMetadata
    {
        $this->requestParameterSupported = $requestParameterSupported;
        return $this;
    }

    public function isRequestUriParameterSupported(): bool
    {
        return $this->requestUriParameterSupported;
    }

    public function setRequestUriParameterSupported(bool $requestUriParameterSupported): ProviderMetadata
    {
        $this->requestUriParameterSupported = $requestUriParameterSupported;
        return $this;
    }

    public function isRequireRequestUriRegistration(): bool
    {
        return $this->requireRequestUriRegistration;
    }

    public function setRequireRequestUriRegistration(bool $requireRequestUriRegistration): ProviderMetadata
    {
        $this->requireRequestUriRegistration = $requireRequestUriRegistration;
        return $this;
    }

    public function getOpPolicyUri(): string
    {
        return $this->opPolicyUri;
    }

    public function setOpPolicyUri(string $opPolicyUri): ProviderMetadata
    {
        $this->opPolicyUri = $opPolicyUri;
        return $this;
    }

    public function getOpTosUri(): string
    {
        return $this->opTosUri;
    }

    public function setOpTosUri(string $opTosUri): ProviderMetadata
    {
        $this->opTosUri = $opTosUri;
        return $this;
    }
}
