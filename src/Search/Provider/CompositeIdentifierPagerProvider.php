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

namespace App\Search\Provider;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Doctrine\RegisterListenersService;
use FOS\ElasticaBundle\Provider\PagerInterface;
use FOS\ElasticaBundle\Provider\PagerfantaPager;
use FOS\ElasticaBundle\Provider\PagerProviderInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Pages an index whose entity has a composite identifier.
 *
 * The bundle's ORMPagerProvider pages with fetch-join pagination, which
 * resolves a page over `WHERE <id> IN (…)` and therefore needs a single
 * identifier field. `Materials` is keyed by (item_id, version_id), so
 * `fos:elastica:populate` fails there — see #5454.
 *
 * Precondition: the index query must return one row per object. A
 * fetch-joined collection would multiply rows and drop objects off the
 * end of a page.
 */
#[Exclude]
final readonly class CompositeIdentifierPagerProvider implements PagerProviderInterface
{
    private const ENTITY_ALIAS = 'a';

    /**
     * @param class-string         $objectClass
     * @param array<string, mixed> $baseOptions
     */
    public function __construct(
        private ManagerRegistry $doctrine,
        private RegisterListenersService $registerListenersService,
        private string $objectClass,
        private array $baseOptions,
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function provide(array $options = []): PagerInterface
    {
        $options = array_replace($this->baseOptions, $options);

        $manager = $this->doctrine->getManagerForClass($this->objectClass);
        $repository = $manager->getRepository($this->objectClass);

        $queryBuilder = $repository->{$options['query_builder_method']}(self::ENTITY_ALIAS);

        // Pagination needs a total order, or page two may repeat page one.
        // Aliases come from the FROM clauses, not from the one passed in:
        // a repository method is free to ignore it, and MaterialsRepository does.
        if ($queryBuilder instanceof QueryBuilder && empty($queryBuilder->getDQLPart('orderBy'))) {
            // Normalises string FROM parts into From objects.
            $queryBuilder->getRootAliases();

            /** @var From[] $fromClauses */
            $fromClauses = $queryBuilder->getDQLPart('from');
            foreach ($fromClauses as $fromClause) {
                $identifiers = $manager->getClassMetadata($fromClause->getFrom())->getIdentifierFieldNames();
                foreach ($identifiers as $identifier) {
                    $queryBuilder->addOrderBy($fromClause->getAlias().'.'.$identifier);
                }
            }
        }

        // fetchJoinCollection: false skips the single-identifier lookup.
        // Rests on the precondition above; pinned by the slice comparison
        // in CompositeIdentifierPagerProviderTest.
        $pager = new PagerfantaPager(
            new Pagerfanta(new QueryAdapter($queryBuilder, fetchJoinCollection: false))
        );

        $this->registerListenersService->register($manager, $pager, $options);

        return $pager;
    }
}
