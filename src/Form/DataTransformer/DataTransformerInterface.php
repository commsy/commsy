<?php
namespace App\Form\DataTransformer;

interface DataTransformerInterface
{
    public function transform($object);

    public function applyTransformation($object, $data);
}