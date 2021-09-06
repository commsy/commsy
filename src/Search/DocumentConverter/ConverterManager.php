<?php


namespace App\Search\DocumentConverter;


class ConverterManager
{

    private iterable $converters;

    /**
     * ConverterManager constructor.
     * @param iterable $converters
     */
    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    /**
     * @param $format
     * @return DocumentConverterInterface|null
     */
    public function getConverter($format): ?DocumentConverterInterface {
        /** @var DocumentConverterInterface $converter */
        foreach ($this->converters as $converter){
            if($converter->supportsFormat($format)){
                return $converter;
            }
        }
        return null;
    }
}