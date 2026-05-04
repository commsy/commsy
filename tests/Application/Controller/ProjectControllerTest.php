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

namespace Tests\Application\Controller;

use App\Entity\Account;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class ProjectControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private int $communityId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $this->portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        // ProjectController routes are mounted under /room/{roomId} where
        // roomId is the *container* community room (not the portal). The
        // portal id does not work here — ProjectService::getCountArray
        // calls $roomManager->getItem($roomId)->getContextID() and crashes
        // on null when there is no legacy room item for the portal id.
        $this->communityId = $this->createCommunityRoom($this->portalId, 'Community-Container');
    }

    public function testListRenders(): void
    {
        $this->client->request('GET', "/room/{$this->communityId}/project");
        $this->assertResponseIsSuccessful();
    }

    public function testFeedRenders(): void
    {
        $this->client->request('GET', "/room/{$this->communityId}/project/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testCreateFormRenders(): void
    {
        $this->client->request('GET', "/room/{$this->communityId}/project/create");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateSubmitProducesProjectAndRedirectsToDetail(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->communityId}/project/create");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('project[save]')->form();
        $form['project[title]'] = 'Forschungs-Projekt';
        $this->client->submit($form);

        // success → redirect to /room/{communityId}/project/{newItemId}
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertMatchesRegularExpression(
            "~^/room/{$this->communityId}/project/\d+$~",
            $location,
            "expected redirect to project detail, got: {$location}"
        );

        // follow redirect → detail page renders, shows the title
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Forschungs-Projekt', $crawler->html());
    }

    public function testDetailRendersForCreatedProject(): void
    {
        $itemId = $this->createProjectViaForm('Mein-Detail-Projekt');

        $this->client->request('GET', "/room/{$this->communityId}/project/{$itemId}");
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Mein-Detail-Projekt', $this->client->getResponse()->getContent());
    }

    public function testDeleteFormRenders(): void
    {
        $itemId = $this->createProjectViaForm('Zum-Löschen');

        $this->client->request('GET', "/room/{$this->communityId}/project/{$itemId}/delete");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testDeleteSubmitWithCorrectConfirmRedirectsToList(): void
    {
        $itemId = $this->createProjectViaForm('Wird-Gelöscht');

        $crawler = $this->client->request('GET', "/room/{$this->communityId}/project/{$itemId}/delete");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_room[delete]')->form();
        // The IdenticalTo constraint expects the uppercased translated 'delete'
        // string from the 'profile' domain in the request locale.
        $locale = $this->client->getResponse()->headers->get('Content-Language') ?? 'en';
        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $form['delete_room[confirm]'] = mb_strtoupper($translator->trans('delete', [], 'profile', $locale));

        $this->client->submit($form);

        $this->assertResponseRedirects("/room/{$this->communityId}/project");
    }

    public function testDeleteSubmitWithBlankConfirmShowsError(): void
    {
        $itemId = $this->createProjectViaForm('Bleibt-Stehen');

        $crawler = $this->client->request('GET', "/room/{$this->communityId}/project/{$itemId}/delete");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_room[delete]')->form();
        $form['delete_room[confirm]'] = '';
        $this->client->submit($form);

        // 422 = invalid form (blank fails NotBlank + IdenticalTo)
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Creates a Project room via the actual /project/create form submit and
     * returns its new itemId. We need a real legacy project room (not a
     * RoomFactory shell) for detail/delete to behave correctly — those code
     * paths read room state via the legacy environment.
     */
    private function createProjectViaForm(string $title): int
    {
        $crawler = $this->client->request('GET', "/room/{$this->communityId}/project/create");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('project[save]')->form();
        $form['project[title]'] = $title;
        $this->client->submit($form);

        $location = $this->client->getResponse()->headers->get('Location');
        if (!preg_match("~^/room/{$this->communityId}/project/(\d+)$~", $location, $m)) {
            $this->fail("Project create did not redirect to detail; got: {$location}");
        }

        return (int) $m[1];
    }

    /**
     * Same as AbstractApplicationTestCase::createRoom, but for community
     * rooms (which act as containers for projects in the ProjectController).
     */
    private function createCommunityRoom(int $portalId, string $title): int
    {
        $crawler = $this->client->request('GET', "/room/{$portalId}/all/create");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('context[save]')->form();
        $form['context[title]'] = $title;
        $form['context[type_select]']->select('community');

        $this->client->submit($form);

        $location = $this->client->getResponse()->headers->get('Location');
        if (!preg_match('~^/portal/\d+/room/(\d+)~', $location, $m)) {
            $this->fail("Community create did not redirect to room detail; got: {$location}");
        }

        return (int) $m[1];
    }
}
