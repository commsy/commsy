<?php
namespace App\Model;

class SearchData
{
    /**
     * @var string|null
     */
    private $phrase;

    /**
     * @var boolean|null
     */
    private $appearsInTitle;

    /**
     * @var boolean|null
     */
    private $appearsInDescription;

    /**
     * @var string|null
     */
    private $selectedReadStatus;

    /**
     * @var array|null associative array of rubrics (key: rubric name, value: count)
     */
    private $rubrics;

    /**
     * @var string|null
     */
    private $selectedRubric;

    /**
     * @var array|null associative array of context titles (key: context title, value: count)
     */
    private $contexts;

    /**
     * @var string|null
     */
    private $selectedContext;

    /**
     * @var array|null associative array of creators (key: creator name, value: count)
     */
    private $creators;

    /**
     * @var string|null $selectedCreator
     */
    private $selectedCreator;

    /**
     * @var array|null associative array of hashtags (key: hashtag name, value: count)
     */
    private $hashtags;

    /**
     * @var string[]|null $selectedHashtags
     */
    private $selectedHashtags;

    /**
     * @var array|null associative array of categories (key: category name, value: count)
     */
    private $categories;

    /**
     * @var string[]|null $selectedCategories
     */
    private $selectedCategories;

    /**
     * @var \DateTime|null $creationDateFrom
     */
    private $creationDateFrom;

    /**
     * @var \DateTime|null $creationDateUntil
     */
    private $creationDateUntil;

    /**
     * @var \DateTime|null $modificationDateFrom
     */
    private $modificationDateFrom;

    /**
     * @var \DateTime|null $modificationDateUntil
     */
    private $modificationDateUntil;

    /**
     * @return string|null
     */
    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    /**
     * @param string|null $phrase
     * @return self
     */
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
     * @return self
     */
    public function setAppearsIn(array $appearsIn): self
    {
        $this->setAppearsInTitle(in_array('title', $appearsIn, true) ? true : false);
        $this->setAppearsInDescription(in_array('description', $appearsIn, true) ? true : false);
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getAppearsInTitle(): ?bool
    {
        return $this->appearsInTitle;
    }

    /**
     * @param boolean $appearsInTitle
     */
    public function setAppearsInTitle(bool $appearsInTitle): self
    {
        $this->appearsInTitle = $appearsInTitle;
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getAppearsInDescription(): ?bool
    {
        return $this->appearsInDescription;
    }

    /**
     * @param boolean $appearsInDescription
     */
    public function setAppearsInDescription(bool $appearsInDescription): self
    {
        $this->appearsInDescription = $appearsInDescription;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSelectedReadStatus(): ?string
    {
        return $this->selectedReadStatus;
    }

    /**
     * @param string|null $selectedReadStatus
     * @return self
     */
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
     * @return self
     */
    public function setRubrics(array $rubrics): self
    {
        $this->rubrics = $rubrics;
        return $this;
    }

    /**
     * @param array $rubrics associative array of rubrics (key: rubric name, value: count)
     * @return self
     */
    public function addRubrics(array $rubrics): self
    {
        foreach ($rubrics as $name => $count) {
            $this->rubrics[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSelectedRubric(): ?string
    {
        return $this->selectedRubric;
    }

    /**
     * @param string|null $selectedRubric
     * @return self
     */
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
     * @return self
     */
    public function setCreators(array $creators): self
    {
        $this->creators = $creators;
        return $this;
    }

    /**
     * @param array $creators associative array of creators (key: creator name, value: count)
     * @return self
     */
    public function addCreators(array $creators): self
    {
        foreach ($creators as $name => $count) {
            $this->creators[$name] = $count;
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSelectedCreator(): ?string
    {
        return $this->selectedCreator;
    }

    /**
     * @param string|null $selectedCreator
     * @return self
     */
    public function setSelectedCreator(?string $selectedCreator): self
    {
        $this->selectedCreator = $selectedCreator;
        return $this;
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
     * @return self
     */
    public function setHashtags(array $hashtags): self
    {
        $this->hashtags = $hashtags;
        return $this;
    }

    /**
     * @param array $hashtags associative array of hashtags (key: hashtag name, value: count)
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @param array $categories associative array of categories (key: category name, value: count)
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
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

    /**
     * @return \DateTime|null
     */
    public function getCreationDateFrom(): ?\DateTime
    {
        return $this->creationDateFrom;
    }

    /**
     * @param \DateTime|null $creationDateFrom
     * @return self
     */
    public function setCreationDateFrom(?\DateTime $creationDateFrom): self
    {
        $this->creationDateFrom = $creationDateFrom;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreationDateUntil(): ?\DateTime
    {
        return $this->creationDateUntil;
    }

    /**
     * @param \DateTime|null $creationDateUntil
     * @return self
     */
    public function setCreationDateUntil(?\DateTime $creationDateUntil): self
    {
        $this->creationDateUntil = $creationDateUntil;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getModificationDateFrom(): ?\DateTime
    {
        return $this->modificationDateFrom;
    }

    /**
     * @param \DateTime|null $modificationDateFrom
     * @return self
     */
    public function setModificationDateFrom(?\DateTime $modificationDateFrom): self
    {
        $this->modificationDateFrom = $modificationDateFrom;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getModificationDateUntil(): ?\DateTime
    {
        return $this->modificationDateUntil;
    }

    /**
     * @param \DateTime|null $modificationDateUntil
     * @return self
     */
    public function setModificationDateUntil(?\DateTime $modificationDateUntil): self
    {
        $this->modificationDateUntil = $modificationDateUntil;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getContexts(): ?array
    {
        return $this->contexts;
    }

    /**
     * @param array|null $contexts
     */
    public function setContexts(?array $contexts): void
    {
        $this->contexts = $contexts;
    }

    /**
     * @return string|null
     */
    public function getSelectedContext(): ?string
    {
        return $this->selectedContext;
    }

    /**
     * @param string|null $selectedContext
     * @return self
     */
    public function setSelectedContext(?string $selectedContext): self
    {
        $this->selectedContext = $selectedContext;
        return $this;
    }

    /**
     * @param array $contexts associative array of context titles (key: context title, value: count)
     * @return self
     */
    public function addContexts(array $contexts): self
    {
        foreach ($contexts as $name => $count) {
            $this->contexts[$name] = $count;
        }
        return $this;
    }
}
