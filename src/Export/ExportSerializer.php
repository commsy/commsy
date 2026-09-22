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

namespace App\Export;

use App\Serializer\UserNormalizer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Normalizes entities for the export document, using the same entities and
 * `export` groups as the interface.
 *
 * Separate from the application serializer: API Platform's item normalizer
 * handles any resource class and drops relations to non-resources, which would
 * silently lose an entry's creator.
 *
 * Wraps a Serializer rather than implementing NormalizerInterface, so
 * autoconfiguration cannot tag it into the application's own chain. Inner
 * normalizers are built here because a Serializer overwrites their
 * setSerializer() back-reference.
 */
final readonly class ExportSerializer
{
    private Serializer $serializer;

    public function __construct(
        UserNormalizer $userNormalizer,
        #[Autowire(service: 'api_platform.serializer.mapping.class_metadata_factory')]
        ClassMetadataFactoryInterface $classMetadataFactory,
        #[Autowire(service: 'serializer.name_converter.metadata_aware')]
        NameConverterInterface $nameConverter,
        PropertyAccessorInterface $propertyAccessor,
        #[Autowire(service: 'property_info')]
        PropertyTypeExtractorInterface $propertyTypeExtractor,
        #[Autowire(service: 'serializer.mapping.class_discriminator_resolver')]
        ClassDiscriminatorResolverInterface $classDiscriminatorResolver,
    ) {
        $this->serializer = new Serializer([
            $userNormalizer,
            new DateTimeNormalizer(),
            new ObjectNormalizer(
                $classMetadataFactory,
                $nameConverter,
                $propertyAccessor,
                $propertyTypeExtractor,
                $classDiscriminatorResolver
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $entity): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($entity, null, ['groups' => ['export']]);

        return $normalized;
    }
}
