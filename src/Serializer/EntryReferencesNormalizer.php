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

use App\Export\ExportableEntry;
use App\Repository\ItemLinkFileRepository;
use App\Repository\LinkItemsRepository;
use App\Repository\LinksRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds to an entry what the room already carries: the categories and hashtags
 * it was filed under, as ids into the room's vocabulary.
 *
 * None of it is a column on the entry. Categories live in `link_items`,
 * hashtags in `links`, attachments in `item_link_file`, and the three tables
 * answer differently — which is why each has its own repository rather than one
 * lookup pretending they are the same.
 *
 * Attachments are referenced by id as well; what they are called and where they
 * sit in the archive stands once in the document.
 */
final class EntryReferencesNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ENTRY_REFERENCES_NORMALIZER_ALREADY_CALLED';

    /**
     * The link type hashtags are stored under.
     */
    private const HASHTAG_LINK = 'buzzword_for';

    /**
     * The item type categories are stored as.
     */
    private const CATEGORY_TYPE = 'tag';

    public function __construct(
        private readonly LinksRepository $links,
        private readonly LinkItemsRepository $linkItems,
        private readonly ItemLinkFileRepository $itemLinkFiles,
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

        /** @var ExportableEntry $data */
        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($data, $format, $context);

        $itemId = $data->getItemId();
        $contextId = $data->getRoomId();

        $normalized['categories'] = $this->sorted(
            $this->linkItems->findLinkedItemIds($itemId, self::CATEGORY_TYPE, $contextId)
        );
        $normalized['hashtags'] = $this->sorted(
            $this->links->findLinkedItemIds($itemId, self::HASHTAG_LINK, $contextId, ExportableEntry::UNVERSIONED)
        );
        $normalized['files'] = $this->sorted(
            array_map('intval', $this->itemLinkFiles->getLinkedFileIds($itemId, ExportableEntry::UNVERSIONED))
        );

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ExportableEntry
            && !($context[self::ALREADY_CALLED] ?? false)
            && in_array('export', $context['groups'] ?? [], true);
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        // Not cacheable: support depends on the context, not the class.
        return [ExportableEntry::class => false];
    }

    /**
     * References are compared, not read — a fixed order keeps two exports of an
     * unchanged room comparable.
     *
     * @param int[] $ids
     *
     * @return list<int>
     */
    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}
