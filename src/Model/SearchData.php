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

namespace App\Model;

use App\Entity\SavedSearch;
use Symfony\Component\Validator\Constraints as Assert;

class SearchData
{
    /**
     * @var SavedSearch|null the currently selected saved search (aka "view")
     */
    private ?\App\Entity\SavedSearch $selectedSavedSearch = null;

    #[Assert\NotBlank(normalizer: 'trim', groups: ['save'])]
    private ?string $selectedSavedSearchTitle = null;

    /**
     * @var SavedSearch[]|null array of all saved searches belonging to the current user's account
     */
    private ?array $savedSearches = null;

    private ?string $phrase = null;

    private ?bool $appearsInTitle = null;

    private ?bool $appearsInDescription = null;

    private ?string $selectedReadStatus = null;

    /**
     * @var array|null associative array of rubrics (key: rubric name, value: count)
     */
    private ?array $rubrics = null;

    private ?string $selectedRubric = null;

    /**
     * @var array|null associative array of context titles (key: context title, value: count)
     */
    private ?array $contexts = null;

    private ?string $selectedContext = null;

    /**
     * @var array|null associative array of creators (key: creator name, value: count)
     */
    private ?array $creators = null;

    private ?string $selectedCreator = null;

    /**
     * @var array|null associative array of todo statuses (key: status int, value: count)
     */
    private ?array $todoStatuses = null;

    private ?int $selectedTodoStatus = null;

    /**
     * @var array|null associative array of hashtags (key: hashtag name, value: count)
     */
    private ?array $hashtags = null;

    /**
     * @var string[]|null
     */
    private ?array $selectedHashtags = null;

    /**
     * @var array|null associative array of categories (key: category name, value: count)
     */
    private ?array $categories = null;

    /**
     * @var string[]|null
     */
    private ?array $selectedCategories = null;

    private ?\DateTime $creationDateFrom = null;

    private ?\DateTime $creationDateUntil = null;

    private ?\DateTime $modificationDateFrom = null;

    private ?\DateTime $modificationDateUntil = null;

    public function getSelectedSavedSearch(): ?SavedSearch
    {
        return $this->selectedSavedSearch;
    }

    public function setSelectedSavedSearch(?SavedSearch $selectedSavedSearch): SearchData
    {
        $this->selectedSavedSearch = $selectedSavedSearch;

        return $this;
    }

    /**
     * @return SavedSearch[]|null
     */
    public function getSavedSearches(): ?array
    {
        return $this->savedSearches;
    }

    /**
     * @param SavedSearch[]|null $savedSearches
     */
    public function setSavedSearches(?array $savedSearches): SearchData
    {
        $this->savedSearches = $savedSearches;

        return $this;
    }

    public function getSelectedSavedSearchId(): int
    {
        if (!$this->selectedSavedSearch || !$this->selectedSavedSearch->getId()) {
            return 0;
        }

        return $this->selectedSavedSearch->getId();
    }

    public function getSelectedSavedSearchTitle(): ?string
    {
        return $this->selectedSavedSearchTitle;
    }

    public function setSelectedSavedSearchTitle(?string $title): SearchData
    {
        $this->selectedSavedSearchTitle = $title;

        return $this;
    }

    private ?string $sortBy = null;

    private ?string $sortOrder = null;

    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    public function setPhrase(?string $phrase): self
    {
        $this->phrase = $phrase;

        return $this;
    }

    /**
     * @return array an array of field names describing the fields that must contain a search term
     */
    public function getAppearsIn(): array
    {
        $appearsIn = [];
        if ($this->getAppearsInTitle()) {
            $appearsIn[] = 'title';
        }
        if ($this->getAppearsInDescription()) {
            $appearsIn[] = 'description';
        }

        return $appearsIn;
    }

    /**
     * @param array $appearsIn an array of field names describing the fields that must contain a search term
     */
    public function setAppearsIn(array $appearsIn): self
    {
        $this->setAppearsInTitle(in_array('title', $appearsIn, true) ? true : false);
        $this->setAppearsInDescription(in_array('description', $appearsIn, true) ? true : false);

        return $this;
    }

    public function getAppearsInTitle(): ?bool
    {
        return $this->appearsInTitle;
    }

    public function setAppearsInTitle(bool $appearsInTitle): self
    {
        $this->appearsInTitle = $appearsInTitle;

        return $this;
    }

    public function getAppearsInDescription(): ?bool
    {
        return $this->appearsInDescription;
    }

    public function setAppearsInDescription(bool $appearsInDescription): self
    {
        $this->appearsInDescription = $appearsInDescription;

        return $this;
    }

    public function getSelectedReadStatus(): ?string
    {
        return $this->selectedReadStatus;
    }

    public function setSelectedReadStatus(?string $selectedReadStatus): self
    {
        $this->selectedReadStatus = $selectedReadStatus;

        return $this;
    }

    /**
     * @return array|null associative array of rubrics (key: rubric name, value: count)
     */
    public function getRubrics(): ?array
    {
        return $this->rubrics;
    }

    /**
     * @param array $rubrics associative array of rubrics (key: rubric name, value: count)
     */
    public function setRubrics(array $rubrics): self
    {
        $this->rubrics = $rubrics;

        return $this;
    }

    /**
     * @param array $rubrics associative array of rubrics (key: rubric name, value: count)
     */
    public function addRubrics(array $rubrics): self
    {
        foreach ($rubrics as $name => $count) {
            $this->rubrics[$name] = $count;
        }

        return $this;
    }

    public function getSelectedRubric(): ?string
    {
        return $this->selectedRubric;
    }

    public function setSelectedRubric(?string $selectedRubric): self
    {
        $this->selectedRubric = $selectedRubric;

        return $this;
    }

    /**
     * @return array|null associative array of creators (key: creator name, value: count)
     */
    public function getCreators(): ?array
    {
        return $this->creators;
    }

    /**
     * @param array $creators associative array of creators (key: creator name, value: count)
     */
    public function setCreators(array $creators): self
    {
        $this->creators = $creators;

        return $this;
    }

    /**
     * @param array $creators associative array of creators (key: creator name, value: count)
     */
    public function addCreators(array $creators): self
    {
        foreach ($creators as $name => $count) {
            $this->creators[$name] = $count;
        }

        return $this;
    }

    public function getSelectedCreator(): ?string
    {
        return $this->selectedCreator;
    }

    public function setSelectedCreator(?string $selectedCreator): self
    {
        $this->selectedCreator = $selectedCreator;

        return $this;
    }

    public function getTodoStatuses(): ?array
    {
        return $this->todoStatuses;
    }

    public function setTodoStatuses(?array $todoStatuses): SearchData
    {
        $this->todoStatuses = $todoStatuses;

        return $this;
    }

    public function addTodoStatuses(array $todoStatuses): SearchData
    {
        foreach ($todoStatuses as $name => $count) {
            $this->todoStatuses[$name] = $count;
        }

        return $this;
    }

    public function getSelectedTodoStatus(): ?int
    {
        return $this->selectedTodoStatus;
    }

    public function setSelectedTodoStatus(?int $selectedTodoStatus): void
    {
        $this->selectedTodoStatus = $selectedTodoStatus;
    }

    /**
     * @return array|null associative array of hashtags (key: hashtag name, value: count)
     */
    public function getHashtags(): ?array
    {
        return $this->hashtags;
    }

    /**
     * @param array $hashtags associative array of hashtags (key: hashtag name, value: count)
     */
    public function setHashtags(array $hashtags): self
    {
        $this->hashtags = $hashtags;

        return $this;
    }

    /**
     * @param array $hashtags associative array of hashtags (key: hashtag name, value: count)
     */
    public function addHashtags(array $hashtags): self
    {
        foreach ($hashtags as $name => $count) {
            $this->hashtags[$name] = $count;
        }

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getSelectedHashtags(): ?array
    {
        return $this->selectedHashtags;
    }

    /**
     * @param string[] $selectedHashtags
     */
    public function setSelectedHashtags(array $selectedHashtags): self
    {
        $this->selectedHashtags = $selectedHashtags;

        return $this;
    }

    /**
     * @return array|null associative array of categories (key: category name, value: count)
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    /**
     * @param array $categories associative array of categories (key: category name, value: count)
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @param array $categories associative array of categories (key: category name, value: count)
     */
    public function addCategories(array $categories): self
    {
        foreach ($categories as $name => $count) {
            $this->categories[$name] = $count;
        }

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getSelectedCategories(): ?array
    {
        return $this->selectedCategories;
    }

    /**
     * @param string[] $selectedCategories
     */
    public function setSelectedCategories(array $selectedCategories): self
    {
        $this->selectedCategories = $selectedCategories;

        return $this;
    }

    /**
     * @return array|null an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function getCreationDateRange(): ?array
    {
        return [
            $this->getCreationDateFrom(),
            $this->getCreationDateUntil(),
            ];
    }

    /**
     * @param array|null $creationDateRange an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function setCreationDateRange(?array $creationDateRange): self
    {
        // start date
        if (isset($creationDateRange[0]) && $creationDateRange[0] instanceof \DateTime) {
            $this->setCreationDateFrom($creationDateRange[0]);
        }
        // end date
        if (isset($creationDateRange[1]) && $creationDateRange[1] instanceof \DateTime) {
            $this->setCreationDateUntil($creationDateRange[1]);
        }

        return $this;
    }

    /**
     * @return array|null an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function getModificationDateRange(): ?array
    {
        return [
            $this->getModificationDateFrom(),
            $this->getModificationDateUntil(),
            ];
    }

    /**
     * @param array|null $modificationDateRange an array of two items, start & end date, which may be \DateTime objects or null
     */
    public function setModificationDateRange(?array $modificationDateRange): self
    {
        if (isset($modificationDateRange[0]) && $modificationDateRange[0] instanceof \DateTime) {
            $this->setModificationDateFrom($modificationDateRange[0]);
        }
        if (isset($modificationDateRange[1]) && $modificationDateRange[1] instanceof \DateTime) {
            $this->setModificationDateUntil($modificationDateRange[1]);
        }

        return $this;
    }

    public function getCreationDateFrom(): ?\DateTime
    {
        return $this->creationDateFrom;
    }

    public function setCreationDateFrom(?\DateTime $creationDateFrom): self
    {
        $this->creationDateFrom = $creationDateFrom;

        return $this;
    }

    public function getCreationDateUntil(): ?\DateTime
    {
        return $this->creationDateUntil;
    }

    public function setCreationDateUntil(?\DateTime $creationDateUntil): self
    {
        $this->creationDateUntil = $creationDateUntil;

        return $this;
    }

    public function getModificationDateFrom(): ?\DateTime
    {
        return $this->modificationDateFrom;
    }

    public function setModificationDateFrom(?\DateTime $modificationDateFrom): self
    {
        $this->modificationDateFrom = $modificationDateFrom;

        return $this;
    }

    public function getModificationDateUntil(): ?\DateTime
    {
        return $this->modificationDateUntil;
    }

    public function setModificationDateUntil(?\DateTime $modificationDateUntil): self
    {
        $this->modificationDateUntil = $modificationDateUntil;

        return $this;
    }

    public function getContexts(): ?array
    {
        return $this->contexts;
    }

    public function setContexts(?array $contexts): void
    {
        $this->contexts = $contexts;
    }

    public function getSelectedContext(): ?string
    {
        return $this->selectedContext;
    }

    public function setSelectedContext(?string $selectedContext): self
    {
        $this->selectedContext = $selectedContext;

        return $this;
    }

    /**
     * @param array $contexts associative array of context titles (key: context title, value: count)
     */
    public function addContexts(array $contexts): self
    {
        foreach ($contexts as $name => $count) {
            $this->contexts[$name] = $count;
        }

        return $this;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortBy(?string $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function getSortOrder(): ?string
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?string $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
