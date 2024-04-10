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

namespace App\Utils;

use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

trait DbalQueryBuilderTrait
{
    protected function addTopicLimit(QueryBuilder $queryBuilder, string $alias, ?int $topicLimit): void
    {
        if (!$topicLimit) return;

        $queryBuilder->leftJoin($alias, 'link_items', 'l21', "l21.deletion_date IS NULL AND l21.first_item_id = $alias.item_id AND l21.second_item_type = 'topic'");
        $queryBuilder->leftJoin($alias, 'link_items', 'l22', "l22.deletion_date IS NULL AND l22.second_item_id = $alias.item_id AND l22.first_item_type = 'topic'");

        if (-1 == $topicLimit) {
            $queryBuilder->andWhere('l21.first_item_id IS NULL AND l21.second_item_id IS NULL');
            $queryBuilder->andWhere('l22.first_item_id IS NULL AND l22.second_item_id IS NULL');
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    'l21.first_item_id = :topicLimit OR l21.second_item_id = :topicLimit',
                    'l22.first_item_id = :topicLimit OR l22.second_item_id = :topicLimit'
                )
            );
            $queryBuilder->setParameter('topicLimit', $topicLimit);
        }
    }

    protected function addGroupLimit(QueryBuilder $queryBuilder, string $alias, ?int $groupLimit): void
    {
        if (!$groupLimit) return;

        $queryBuilder->leftJoin($alias, 'link_items', 'l31', "l31.deletion_date IS NULL AND l31.first_item_id = $alias.item_id AND l31.second_item_type = 'group'");
        $queryBuilder->leftJoin($alias, 'link_items', 'l32', "l32.deletion_date IS NULL AND l32.second_item_id = $alias.item_id AND l32.first_item_type = 'group'");

        if (-1 == $this->_group_limit) {
            $queryBuilder->andWhere('l31.first_item_id IS NULL AND l31.second_item_id IS NULL');
            $queryBuilder->andWhere('l32.first_item_id IS NULL AND l32.second_item_id IS NULL');
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    'l31.first_item_id = :groupLimit OR l31.second_item_id = :groupLimit',
                    'l32.first_item_id = :groupLimit OR l32.second_item_id = :groupLimit'
                )
            );
            $queryBuilder->setParameter('groupLimit', $this->_group_limit);
        }
    }

    protected function addTagLimit(QueryBuilder $queryBuilder, string $alias, ?array $tagLimit): void
    {
        if (!$tagLimit) return;

        $queryBuilder->leftJoin($alias, 'link_items', 'l41', "l41.deletion_date IS NULL AND l41.first_item_id = $alias.item_id AND l41.second_item_type = 'tag'");
        $queryBuilder->leftJoin($alias, 'link_items', 'l42', "l42.deletion_date IS NULL AND l42.second_item_id = $alias.item_id AND l42.first_item_type = 'tag'");

        if (isset($tagLimit[0]) && -1 == $tagLimit[0]) {
            $queryBuilder->andWhere('l41.first_item_id IS NULL AND l41.second_item_id IS NULL');
            $queryBuilder->andWhere('l42.first_item_id IS NULL AND l42.second_item_id IS NULL');
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->in('l41.first_item_id', ':tagIdArray'),
                        $queryBuilder->expr()->in('l41.second_item_id', ':tagIdArray')
                    ),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->in('l42.first_item_id', ':tagIdArray'),
                        $queryBuilder->expr()->in('l42.second_item_id', ':tagIdArray')
                    )
                )
            );
            $queryBuilder->setParameter('tagIdArray', $tagLimit, ArrayParameterType::INTEGER);
        }
    }

    protected function addBuzzwordLimit(QueryBuilder $queryBuilder, string $alias, ?int $buzzwordLimit): void
    {
        if (!$buzzwordLimit) return;

        if (-1 == $buzzwordLimit) {
            $queryBuilder->leftJoin($alias, 'links', 'l6', "l6.from_item_id = $alias.item_id AND l6.link_type='buzzword_for'");
            $queryBuilder->leftJoin($alias, 'labels', 'buzzwords', "l6.to_item_id = buzzwords.item_id AND buzzwords.type = 'buzzword'");
            $queryBuilder->andWhere('l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL');
        } else {
            $queryBuilder->innerJoin($alias, 'links', 'l6', "l6.from_item_id = $alias.item_id AND l6.link_type='buzzword_for'");
            $queryBuilder->innerJoin($alias, 'labels', 'buzzwords', "l6.to_item_id = buzzwords.item_id AND buzzwords.type = 'buzzword'");
            $queryBuilder->andWhere('buzzwords.item_id = :buzzwordLimit');
            $queryBuilder->setParameter('buzzwordLimit', $buzzwordLimit);
        }
    }

    protected function addRefIdLimit(QueryBuilder $queryBuilder, string $alias, ?int $refIdLimit): void
    {
        if (!$refIdLimit) return;

        $queryBuilder->innerJoin($alias, 'link_items', 'l5', "l5.deleter_id IS NULL AND (
                (l5.first_item_id = $alias.item_id AND l5.second_item_id = :refIdLimit) OR (l5.second_item_id = $alias.item_id AND l5.first_item_id = :refIdLimit)
            )");
        $queryBuilder->setParameter('refIdLimit', $refIdLimit);
    }

    protected function addInactiveEntriesLimit(QueryBuilder $queryBuilder, string $alias, string $inactiveLimit): void
    {
        switch ($inactiveLimit) {
            case self::SHOW_ENTRIES_ONLY_ACTIVATED:
                $queryBuilder->andWhere("$alias.activation_date IS NULL OR $alias.activation_date <= NOW()");
                break;
            case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
                $queryBuilder->andWhere("$alias.activation_date IS NOT NULL AND $alias.activation_date > NOW()");
                break;
        }
    }

    protected function addContextLimit(QueryBuilder $queryBuilder, string $alias, array|int|null $contextLimit): void
    {
        if (!$contextLimit) return;

        if (is_array($contextLimit)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in("$alias.context_id", ':roomArrayLimit'));
            $queryBuilder->setParameter('roomArrayLimit', $contextLimit, ArrayParameterType::INTEGER);
        } else {
            $queryBuilder->andWhere("$alias.context_id = :roomLimit");
            $queryBuilder->setParameter('roomLimit', $contextLimit);
        }
    }

    protected function addDeleteLimit(QueryBuilder $queryBuilder, string $alias, bool $excludeDeleted): void
    {
        if ($excludeDeleted) {
            $queryBuilder->andWhere("$alias.deleter_id IS NULL");
        }
    }

    protected function addCreatorLimit(QueryBuilder $queryBuilder, string $alias, ?int $creatorLimit): void
    {
        if (!$creatorLimit) return;

        $queryBuilder->andWhere("$alias.creator_id = :refUserLimit");
        $queryBuilder->setParameter('refUserLimit', $creatorLimit);
    }

    protected function addModifiedWithinLimit(QueryBuilder $queryBuilder, string $alias, ?int $numDays): void
    {
        if (!$numDays) return;

        $queryBuilder->andWhere("$alias.modification_date >= DATE_SUB(CURRENT_DATE, INTERVAL :ageLimit DAY)");
        $queryBuilder->setParameter('ageLimit', $numDays);
    }

    protected function addModifiedAfterLimit(QueryBuilder $queryBuilder, string $alias, ?DateTime $date): void
    {
        if (!$date) return;

        $queryBuilder->andWhere("$alias.modification_date >= :modificationDate");
        $queryBuilder->setParameter('modificationDate', $date->format('Y-m-d H:i:s'));
    }

    protected function addCreatedWithinLimit(QueryBuilder $queryBuilder, string $alias, ?int $numDays): void
    {
        if (!$numDays) return;

        $queryBuilder->andWhere("$alias.creation_date >= DATE_SUB(CURRENT_DATE, INTERVAL :existenceLimit DAY)");
        $queryBuilder->setParameter('existenceLimit', $numDays);
    }

    protected function addIdLimit(QueryBuilder $queryBuilder, string $alias, ?array $ids): void
    {
        if (!$ids) return;

        $queryBuilder->andWhere($queryBuilder->expr()->in("$alias.item_id", ':idArrayLimit'));
        $queryBuilder->setParameter('idArrayLimit', $ids, ArrayParameterType::INTEGER);
    }

    protected function addNotIdLimit(QueryBuilder $queryBuilder, string $alias, ?array $ids): void
    {
        if (!$ids) return;

        $queryBuilder->andWhere($queryBuilder->expr()->notIn("$alias.item_id", ':idArrayLimit'));
        $queryBuilder->setParameter('idArrayLimit', $ids, ArrayParameterType::INTEGER);
    }
}
