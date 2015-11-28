<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

interface DataTransformerInterface
{
    public function transform($object);

    public function applyTransformation($object, $data);
}