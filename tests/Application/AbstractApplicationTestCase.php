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

namespace Tests\Application;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractApplicationTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    public function setUp(): void
    {
        static::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    protected function loginAsRoot(): void
    {
        // This would be a better solution, but does not work yet
        //$accountRepository = static::getContainer()->get(AccountsRepository::class);
        //$rootUser = $accountRepository->findOneBy(['username' => 'root']);
        //$client->loginUser($rootUser);

        $this->client->request('GET', '/login/server');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('login_local', [
            'email' => 'root',
            'password' => 'pcxEmQj6QzE5',
        ]);
        $this->assertResponseRedirects('/portal/server/enter');
    }

    protected function loginAsUser(int $portalId, string $username, string $password): void
    {
        // @see loginAsRoot

        $this->client->request('GET', "/login/{$portalId}");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('login_local', [
            'email' => $username,
            'password' => $password,
        ]);
        $this->assertResponseRedirects("/portal/{$portalId}/enter");
    }

    protected function logout(): void
    {
        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects('/');
    }

    protected function createRoom(int $portalId, string $title): int
    {
        $crawler = $this->client->request('GET', "/room/{$portalId}/all/create");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('context[save]')->form();

        $form['context[title]'] = $title;
        $form['context[type_select]']->select('project');

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $locationHeader = $response->headers->get('Location');
        preg_match('~^/portal/\d+/room/(\d+)~', $locationHeader, $matches);
        $roomId = intval($matches[1]);

        $this->assertResponseRedirects("/portal/{$portalId}/room/$roomId");

        return $roomId;
    }
}
