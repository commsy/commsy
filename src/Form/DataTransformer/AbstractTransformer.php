<?php


namespace App\Form\DataTransformer;


abstract class AbstractTransformer implements DataTransformerInterface
{
    protected $entity;

    /**
     * Method to check if a entity is supported by this transformer
     * @param string $entity
     * @return bool
     */
    public function supportsFormat(string $entity) : bool {
        return $this->entity == $entity;
    }
}