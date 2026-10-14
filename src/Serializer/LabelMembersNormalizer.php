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

use App\Entity\Labels;
use App\Entity\User;
use App\Item\ItemType;
use App\Repository\LinkItemsRepository;
use App\Repository\UserRepository;
use App\Rubric\Label\LabelType;
use App\Serializer\Schema\FieldDescriber;
use App\Serializer\Schema\FieldDescription;
use App\Serializer\Schema\SchemaReferences;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds the people in a group.
 *
 * A group keeps its members the way an entry keeps its categories: as links
 * rather than as a column. Every label type could carry such a link, but only a
 * group shows them, so only a group is asked.
 *
 * The people are rendered as they are anywhere else in the document, which is
 * what keeps an export from showing an address the room hides.
 */
// Not shared: whichever serializer it is given to points it back at itself, and
// the export and the interface each need it pointing at their own chain.
#[Autoconfigure(shared: false)]
final class LabelMembersNormalizer implements NormalizerInterface, NormalizerAwareInterface, FieldDescriber
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'LABEL_MEMBERS_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly LinkItemsRepository $linkItems,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Labels $data */
        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        $memberIds = $this->linkItems->findLinkedItemIds(
            $data->getItemId(),
            ItemType::User->value,
            $data->getRoomId()
        );

        $normalized['members'] = array_map(
            fn (object $user): mixed => $this->normalizer->normalize($user, $format, $context),
            $this->users->findActiveByItemIds($memberIds)
        );

        return $normalized;
    }

    public function describe(string $class, SchemaReferences $references): ?FieldDescription
    {
        if (!is_a($class, Labels::class, true)) {
            return null;
        }

        return new FieldDescription([
            'members' => [
                'type' => 'array',
                'items' => $references->to(User::class),
                'description' => 'Only on a group: the people in it.',
            ],
        ], optional: ['members']);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Labels
            && LabelType::Group->value === $data->getType()
            && !($context[self::ALREADY_CALLED] ?? false)
            && in_array('export', $context['groups'] ?? [], true);
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Not cacheable: support depends on the context, not the class.
        return [Labels::class => false];
    }
}
