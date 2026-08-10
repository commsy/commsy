<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Entity\Portal;
use App\Entity\User;
use App\Mail\Messages\InvitationMessage;
use App\Services\CurrentUserResolver;
use App\Services\PortalUrlResolver;
use cs_room_item;
use PHPUnit\Framework\TestCase;

class InvitationMessageTest extends TestCase
{
    public function testMessageExposesSubjectTemplateAndParameters(): void
    {
        $portal = $this->createMock(Portal::class);
        $portal->method('getId')->willReturn(42);
        $portal->method('getTitle')->willReturn('Test Portal');

        $room = $this->createMock(cs_room_item::class);
        $room->method('getItemId')->willReturn(1234);
        $room->method('getTitle')->willReturn('Test Room');

        $sender = $this->createMock(User::class);
        $sender->method('getFullname')->willReturn('Erika Mustermann');

        $currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $currentUserResolver->method('getUser')->willReturn($sender);

        $portalUrlResolver = $this->createMock(PortalUrlResolver::class);
        $portalUrlResolver->method('resolve')->with($portal)
            ->willReturn('https://www.unicommsy.uni-hamburg.de');

        $message = new InvitationMessage($currentUserResolver, $portalUrlResolver, $portal, $room, 'tok-123');

        self::assertSame('mail.invitation_subject', $message->getSubject());
        self::assertSame('mail/invitation.html.twig', $message->getTemplateName());
        self::assertSame(['portal' => 'Test Portal'], $message->getTranslationParameters());

        $parameters = $message->getParameters();
        self::assertSame($room, $parameters['room']);
        self::assertSame($portal, $parameters['portal']);
        self::assertSame('tok-123', $parameters['token']);
        self::assertSame('Erika Mustermann', $parameters['senderName']);
        self::assertSame('https://www.unicommsy.uni-hamburg.de', $parameters['portalUrl']);
    }

    public function testSenderNameFallsBackToEmptyStringWithoutCurrentUser(): void
    {
        $portal = $this->createMock(Portal::class);
        $room = $this->createMock(cs_room_item::class);

        $currentUserResolver = $this->createMock(CurrentUserResolver::class);
        $currentUserResolver->method('getUser')->willReturn(null);

        $portalUrlResolver = $this->createMock(PortalUrlResolver::class);

        $message = new InvitationMessage($currentUserResolver, $portalUrlResolver, $portal, $room, 'tok-123');

        self::assertSame('', $message->getParameters()['senderName']);
    }
}
