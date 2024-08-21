<?php

namespace Tests\Unit\WOPI;

use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Discovery\Response\ProofKey;
use App\WOPI\Discovery\Response\WOPIDiscovery;
use App\WOPI\Verification\ProofKeyValidator;
use Codeception\Test\Unit;
use DG\BypassFinals;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ProofKeyValidatorTest extends Unit
{
    protected function _before()
    {
        BypassFinals::enable();
    }

    public function testProofKeyIsValid()
    {
        // See https://github.com/Microsoft/Office-Online-Test-Tools-and-Documentation/blob/master/samples/SampleWopiHandler/SampleWopiHandler.UnitTests/ProofKeyTests.cs
        $discoveryService = $this->makeEmpty(DiscoveryService::class, [
            'getWOPIDiscovery' => $this->makeEmpty(WOPIDiscovery::class, [
                'getProofKey' => $this->makeEmpty(ProofKey::class, [
                    'getValue' => 'BgIAAACkAABSU0ExAAgAAAEAAQDFEthb5dkE+fGnJgsmY3IXmoFxj1cOwVYLpLNTEksnVRzbXcPfaSl/kFxn5b4QajhH1sTtXECZY6ZUyiDi1NG5ukFc9Fppgt0ywnuJqNBRWPfvLTOaVZRTtr8X8hqL+dPldOI3qFUW2zF6DEsAO9y74l3s6MqNjawCME5X0jb28TOrbXXsDfIGLEN3VBFO3wyhlRZKOmR9ZiqxQbpOz0Ltgv3HYci9OVN9c8YYV5T+fHI0Wtxg4F9lJHlB6MHPV9seVqr4ieM027NG89LhHm9BJEtceII09JgmkwLFUB/s2YGirUwZewk0efw1GL861PE7Vjdn2bIdmGSCRfFQlnPQ',
                    //'modulus' => '0HOWUPFFgmSYHbLZZzdWO/HUOr8YNfx5NAl7GUytooHZ7B9QxQKTJpj0NIJ4XEskQW8e4dLzRrPbNOOJ+KpWHttXz8HoQXkkZV/gYNxaNHJ8/pRXGMZzfVM5vchhx/2C7ULPTrpBsSpmfWQ6ShaVoQzfThFUd0MsBvIN7HVtqzPx9jbSV04wAqyNjcro7F3iu9w7AEsMejHbFlWoN+J05dP5ixryF7+2U5RVmjMt7/dYUdCoiXvCMt2CaVr0XEG6udHU4iDKVKZjmUBc7cTWRzhqEL7lZ1yQfylp38Nd2xxVJ0sSU7OkC1bBDlePcYGaF3JjJgsmp/H5BNnlW9gSxQ==',
                    //'exponent' => 'AQAB',
                    'getOldValue' => 'BgIAAACkAABSU0ExAAgAAAEAAQClucwAqDjPvK3/nrmf51f8tsDkBeuCQnz8qYn66hftzDfzn2zR8RlGDXfm3wKsTBwDDzL2QXsJm09c9/p8FpPi39lpM31dfO/7KyqbQ4x/lfa9Y6SyPIP2eG/tKrXbkeIoWfyCtuQqeY6HrmijYOKc6IN4WOJWaWl2etVu8FRnk2plvlEHLm0N8x5ytCSCd776XXHmY7UzMiR0p6IGLpVfKM0ulQY65bmCGvDxkZwSOJyDV/hkQDGluMRLqQHrSAbnAn+ZKh3NTNspveigbUkakPIX8wc2I8ztzjvXXOp3KPzNFfQQGjLMo51CILxH6pRUvb4q8+VDijfiWvdvafq7',
                    //'oldModulus' => 'u/ppb/da4jeKQ+XzKr69VJTqR7wgQp2jzDIaEPQVzfwod+pc1zvO7cwjNgfzF/KQGkltoOi9KdtMzR0qmX8C5wZI6wGpS8S4pTFAZPhXg5w4EpyR8fAagrnlOgaVLs0oX5UuBqKndCQyM7Vj5nFd+r53giS0ch7zDW0uB1G+ZWqTZ1TwbtV6dmlpVuJYeIPonOJgo2iuh455KuS2gvxZKOKR27Uq7W949oM8sqRjvfaVf4xDmyor++98XX0zadnf4pMWfPr3XE+bCXtB9jIPAxxMrALf5ncNRhnx0Wyf8zfM7Rfq+omp/HxCgusF5MC2/Ffnn7me/628zzioAMy5pQ==',
                    //'oldExponent' => 'AQAB',
                ]),
            ]),
        ]);

        $containerBag = $this->makeEmpty(ContainerBagInterface::class, [
            'get' => fn(string $id) => $id === 'commsy.online_office.proofkey_validation',
        ]);

        $validator = new ProofKeyValidator($discoveryService, $containerBag);
        $this->assertTrue($validator->isValid(
            'yZhdN1qgywcOQWhyEMVpB6NE3pvBksvcLXsrFKXNtBeDTPW%2fu62g2t%2fOCWSlb3jUGaz1zc%2fzOzbNgAredLdhQI1Q7sPPqUv2owO78olmN74DV%2fv52OZIkBG%2b8jqjwmUobcjXVIC1BG9g%2fynMN0itZklL2x27Z2imCF6xELcQUuGdkoXBj%2bI%2bTlKM',
            635655897610773532,
            'https://contoso.com/wopi/files/vHxYyRGM8VfmSGwGYDBMIQPzuE+sSC6kw+zWZw2Nyg?access_token=yZhdN1qgywcOQWhyEMVpB6NE3pvBksvcLXsrFKXNtBeDTPW%2fu62g2t%2fOCWSlb3jUGaz1zc%2fzOzbNgAredLdhQI1Q7sPPqUv2owO78olmN74DV%2fv52OZIkBG%2b8jqjwmUobcjXVIC1BG9g%2fynMN0itZklL2x27Z2imCF6xELcQUuGdkoXBj%2bI%2bTlKM',
            'IflL8OWCOCmws5qnDD5kYMraMGI3o+T+hojoDREbjZSkxbbx7XIS1Av85lohPKjyksocpeVwqEYm9nVWfnq05uhDNGp2MsNyhPO9unZ6w25Rjs1hDFM0dmvYx8wlQBNZ/CFPaz3inCMaaP4PtU85YepaDccAjNc1gikdy3kSMeG1XZuaDixHvMKzF/60DMfLMBIu5xP4Nt8i8Gi2oZs4REuxi6yxOv2vQJQ5+8Wu2Olm8qZvT4FEIQT9oZAXebn/CxyvyQv+RVpoU2gb4BreXAdfKthWF67GpJyhr+ibEVDoIIolUvviycyEtjsaEBpOf6Ne/OLRNu98un7WNDzMTQ==',
            'lWBTpWW8q80WC1eJEH5HMnGka4/LUF7zjUPqBwRMO0JzVcnjICvMP2TZPB2lJfy/4ctIstCN6P1t38NCTTbLWlXuE+c4jqL9r2HPAdPPcPYiBAE1Evww93GpxVyOVcGADffshQvfaYFCfwL9vrBRstaQuWI0N5QlBCtWbnObF4dFsFWRRSZVU0X9YcNGhVX1NkVFVfCKG63Q/JkL+TnsJ7zqb7ZQpbS19tYyy4abtlGKWm3Zc1Jq9hPI3XVpoARXEO8cW6lT932QGdZiNr9aW2c15zTC6WiTxVeu7RW2Y0meX+Sfyrfu7GFb5JXDJAq8ZrUEUWABv1BOhHz5vLYHIA==',
            false
        ));
    }
}
