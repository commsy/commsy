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

namespace Tests\Api\WOPI;

use App\WOPI\Auth\AccessTokenGenerator;
use App\WOPI\Discovery\DiscoveryService;
use App\WOPI\Permission\WOPIPermission;
use App\WOPI\Verification\ProofKeyValidator;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Tests\Api\AbstractApiTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\FilesFactory;

/**
 * With proof keys switched on, a request of the office server has to carry
 * them besides its access token.
 */
final class ProofKeyCheckTest extends AbstractApiTestCase
{
    public function testARequestWithoutProofKeyIsRefused(): void
    {
        $account = AccountFactory::createOne();
        $file = FilesFactory::createOne();
        $token = static::getContainer()->get(AccessTokenGenerator::class)->generateToken($account, $file, WOPIPermission::VIEW);

        $client = static::createClient();
        $params = $this->createStub(ContainerBagInterface::class);
        $params->method('get')->willReturn(true);
        static::getContainer()->set(ProofKeyValidator::class, new ProofKeyValidator(static::getContainer()->get(DiscoveryService::class), $params));

        $client->request('GET', "/api/v2/wopi/files/{$file->getFilesId()}?access_token=$token", [
            'headers' => ['Accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
