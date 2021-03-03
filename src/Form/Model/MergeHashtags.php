<?php


namespace App\Form\Model;


use App\Entity\Labels;
use Symfony\Component\Validator\Constraints as Assert;

class MergeHashtags
{
    /**
     * @var Labels
     */
    private $first;

    /**
     * @var Labels
     * @Assert\NotIdenticalTo(propertyPath="first", message="Your selection must differ.")
     */
    private $second;

    /**
     * @return Labels
     */
    public function getFirst(): ?Labels
    {
        return $this->first;
    }

    /**
     * @param Labels $first
     * @return self
     */
    public function setFirst(Labels $first): self
    {
        $this->first = $first;
        return $this;
    }

    /**
     * @return Labels
     */
    public function getSecond(): ?Labels
    {
        return $this->second;
    }

    /**
     * @param Labels $second
     * @return self
     */
    public function setSecond(Labels $second): self
    {
        $this->second = $second;
        return $this;
    }
}