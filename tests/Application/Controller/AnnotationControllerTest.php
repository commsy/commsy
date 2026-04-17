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
use App\Entity\Annotations;
use App\Entity\Announcement;
use App\Entity\Room;
use App\Entity\User;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class AnnotationControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testFeedRendersForAnnouncementWithAnnotations(): void
    {
        $announcement = $this->createAnnouncement();
        $this->createAnnotation($announcement->getItemId());

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/annotation/feed/{$announcement->getItemId()}/0"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testCreateAnnotationRedirectsToAnnouncementDetail(): void
    {
        $announcement = $this->createAnnouncement();

        // render the detail page first so we can grab the create-form's CSRF
        // token — annotations are submitted as a sub-form on the parent item.
        $crawler = $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/announcement/{$announcement->getItemId()}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('annotation[save]')->form();
        $form['annotation[description]'] = 'Frisch annotiert';
        $this->client->submit($form);

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $status);
        $this->assertLessThan(400, $status);
    }

    public function testEditAnnotationFormRenders(): void
    {
        $announcement = $this->createAnnouncement();
        $annotation = $this->createAnnotation($announcement->getItemId());

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/annotation/{$annotation->getItemId()}/edit"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testSuccessPageRenders(): void
    {
        $announcement = $this->createAnnouncement();
        $annotation = $this->createAnnotation($announcement->getItemId());

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/annotation/{$annotation->getItemId()}/success"
        );

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteAnnotationReturnsJson(): void
    {
        $announcement = $this->createAnnouncement();
        $annotation = $this->createAnnotation($announcement->getItemId());

        $this->client->request(
            'GET',
            "/room/{$this->room->getItemId()}/annotation/{$annotation->getItemId()}/delete"
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($this->client->getResponse()->getContent());
        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($payload['deleted'] ?? false);
    }

    private function createAnnouncement(): Announcement
    {
        return AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    private function createAnnotation(int $linkedItemId): Annotations
    {
        return AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $linkedItemId,
        ]);
    }
}
