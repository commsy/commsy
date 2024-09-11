<?php

namespace App\Security;

use App\WOPI\Verification\ProofKeyValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\QueryParameterTokenExtractor;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\Service\Attribute\Required;

final class WOPIJWTAuthenticator extends JWTAuthenticator
{
    private ProofKeyValidator $proofKeyValidator;

    #[Required]
    public function setProofKeyValidator(ProofKeyValidator $proofKeyValidator): void
    {
        $this->proofKeyValidator = $proofKeyValidator;
    }

    protected function getTokenExtractor(): TokenExtractorInterface
    {
        // WOPI clients aren't required to pass the access token in the Authorization header,
        // but they must send it as a URL parameter in all WOPI operations. So, for maximum compatibility,
        // WOPI hosts should either use the URL parameter in all cases, or fall back to it if the
        // Authorization header isn't included in the request.
        $chainExtractor = parent::getTokenExtractor();
        $chainExtractor->addExtractor(new QueryParameterTokenExtractor('access_token'));

        return $chainExtractor;
    }

    public function doAuthenticate(Request $request): Passport
    {
        if (!$this->proofKeyValidator->isRequestValid($request)) {
            throw new LogicException('proof key validation failed!');
        }

        return parent::doAuthenticate($request);
    }
}
