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

namespace App\Serializer;

use App\Entity\User;
use App\User\UserStatus;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Renders a person for output outside the application: an entry's creator or
 * modifier. Shared with the interface once the rubric resources ship.
 *
 * Ships the room status so consumers can filter by role themselves; it is the
 * current status, as no history of past roles exists.
 *
 * Includes the address only when visible, matching what the interface shows
 * (`cs_user_item::isEmailVisible()`, where unset counts as visible).
 *
 * Built by hand rather than delegating to the object normalizer, which would
 * expose the rest of the user row.
 */
final readonly class UserNormalizer implements NormalizerInterface
{
    private const EMAIL_VISIBILITY = 'EMAIL_VISIBILITY';
    private const EMAIL_HIDDEN = '-1';

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /* @var User $data */
        return [
            'id' => $data->getItemId(),
            'name' => $data->getFullname(),
            'email' => $this->emailIfVisible($data),
            'roomStatus' => UserStatus::fromUser($data)->slug(),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof User && in_array('export', $context['groups'] ?? [], true);
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Not cacheable: support depends on the context groups, not the class.
        return [User::class => false];
    }

    private function emailIfVisible(User $user): ?string
    {
        $visibility = $user->getExtras()[self::EMAIL_VISIBILITY] ?? null;

        return self::EMAIL_HIDDEN === $visibility ? null : $user->getEmail();
    }
}
