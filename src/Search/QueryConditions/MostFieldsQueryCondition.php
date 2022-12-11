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

namespace App\Search\QueryConditions;

use DateTime;
use Elastica\Query\MultiMatch;

class MostFieldsQueryCondition implements QueryConditionInterface
{
    private ?string $query = null;

    public function setQuery(string $query): MostFieldsQueryCondition
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return MultiMatch[]
     */
    public function getConditions(): array
    {
        if ('' === $this->query) {
            return [];
        }

        $multiMatch = new MultiMatch();
        $multiMatch->setQuery($this->query);
        $multiMatch->setType('most_fields');
//        $multiMatch->setTieBreaker(0.3);
//        $multiMatch->setMinimumShouldMatch('80%');

        $fields = [
            // first level title
            'title^5',
            'title.raw^20',

            // description
            'description^1.7',

            // date
//            'modificationDate',

            // files
            'attachments.filename^1.5',
            'attachments.filename_no_ext^1.5',
            'attachments.attachment.content^1.6',
//            'discussionarticles.files.content',
//            'steps.files.content',
//            'sections.files.content',

            // creator, sections
            'creator.fullName^1.5',

            // tags
            'tags^1.4',

            // discussion articles
            'discussionarticles.subject^1.3',
            'discussionarticles.description^1.3',
            'discussionarticles.filesRaw',

            // user
            'fullName',

            // others
            'steps.title',
            'sections.title',

            'steps.description',
            'sections.description',

            'steps.filesRaw',
            'sections.filesRaw',

            'userId',
//            'creationDate',
//            'endDate',
//            'datetimeStart',
//            'datetimeEnd',
//            'date',
            'hashtags',

            'annotations',

            'contactPersons',
            'roomDescription',

            'filesRaw',
        ];

        /**
         * In order to search in datetime fields we must ensure to send only search strings that are already in
         * a valid format.
         */
        $queryAsDate = (\DateTime::createFromFormat('d.m.Y', $this->query)) ?: \DateTime::createFromFormat('Y-m-d', $this->query);
        if ($queryAsDate) {
            $fields[] = 'modificationDate';
            $multiMatch->setQuery($queryAsDate->format('Y-m-d'));
        }

        $multiMatch->setFields($fields);

        return [$multiMatch];
    }

    public function getOperator(): string
    {
        return QueryConditionInterface::BOOL_MUST;
    }
}
