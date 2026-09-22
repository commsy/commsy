<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Serializer;

use App\Entity\Room;
use App\Entity\User;
use App\Serializer\UserNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Pins that the export applies the person's own visibility setting.
 */
class UserNormalizerTest extends TestCase
{
    public function testItRendersTheCreatorWithNameStatusAndAddress(): void
    {
        $user = $this->user(status: 3, email: 'moderation@example.org');

        $this->assertSame(
            [
                'id' => 4711,
                'name' => 'Erika Musterfrau',
                'email' => 'moderation@example.org',
                'roomStatus' => 'moderator',
            ],
            (new UserNormalizer())->normalize($user, null, ['groups' => ['export']])
        );
    }

    public function testAHiddenAddressIsLeftOut(): void
    {
        $user = $this->user(email: 'geheim@example.org', extras: ['EMAIL_VISIBILITY' => '-1']);

        $normalized = (new UserNormalizer())->normalize($user, null, ['groups' => ['export']]);

        $this->assertNull($normalized['email']);
        $this->assertSame('Erika Musterfrau', $normalized['name'], 'the name still comes along');
    }

    /**
     * Legacy counts an unset setting as visible; tightening it would hide
     * addresses the interface shows.
     */
    public function testAnUnsetSettingCountsAsVisible(): void
    {
        $user = $this->user(email: 'offen@example.org', extras: null);

        $normalized = (new UserNormalizer())->normalize($user, null, ['groups' => ['export']]);

        $this->assertSame('offen@example.org', $normalized['email']);
    }

    public function testAnyOtherVisibilityValueCountsAsVisible(): void
    {
        $user = $this->user(email: 'offen@example.org', extras: ['EMAIL_VISIBILITY' => '1']);

        $normalized = (new UserNormalizer())->normalize($user, null, ['groups' => ['export']]);

        $this->assertSame('offen@example.org', $normalized['email']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statusProvider')]
    public function testTheRoomStatusIsAName(int $status, string $expected): void
    {
        $normalized = (new UserNormalizer())->normalize(
            $this->user(status: $status),
            null,
            ['groups' => ['export']]
        );

        $this->assertSame($expected, $normalized['roomStatus']);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function statusProvider(): iterable
    {
        yield 'guest' => [0, 'guest'];
        yield 'requested' => [1, 'requested'];
        yield 'member' => [2, 'user'];
        yield 'moderator' => [3, 'moderator'];
        yield 'read only' => [4, 'readonly'];
    }

    public function testItOnlyAppliesUnderTheExportGroup(): void
    {
        $normalizer = new UserNormalizer();
        $user = $this->user();

        $this->assertTrue($normalizer->supportsNormalization($user, null, ['groups' => ['export']]));
        $this->assertFalse(
            $normalizer->supportsNormalization($user, null, ['groups' => ['api']]),
            'the interface renders people its own way'
        );
        $this->assertFalse($normalizer->supportsNormalization($user, null, []));
        $this->assertFalse($normalizer->supportsNormalization(new Room(), null, ['groups' => ['export']]));
    }

    /**
     * @param array<string, string>|null $extras
     */
    private function user(int $status = 2, string $email = 'person@example.org', ?array $extras = null): User
    {
        $user = new User();
        $user->itemId = 4711;
        $user->setStatus($status);
        $user->setFirstname('Erika');
        $user->setLastname('Musterfrau');
        $user->setEmail($email);

        // setExtras() takes no null; an untouched setting simply has none.
        if (null !== $extras) {
            $user->setExtras($extras);
        }

        return $user;
    }
}
