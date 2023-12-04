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

namespace App\Search\Transformer;

use FOS\ElasticaBundle\HybridResult;
use FOS\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Holds a collection of transformers for an index wide transformation.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 * @author Insekticid <insekticid+fos@exploit.cz>
 */
class ElasticaToModelTransformerCollection implements ElasticaToModelTransformerInterface
{
    /**
     * @var ElasticaToModelTransformerInterface[]
     */
    protected array $transformers = [];

    /**
     * @param ElasticaToModelTransformerInterface[] $transformers
     */
    public function __construct(
        array $transformers,
        ParameterBagInterface $parameterBag
    ) {
        $indexPrefix = $parameterBag->get('commsy.elastic.prefix');
        foreach ($transformers as $name => $transformer) {
            $this->transformers[$indexPrefix.'_'.$name] = $transformer;
        }
    }

    public function getObjectClass(): string
    {
        return implode(',', array_map(fn (ElasticaToModelTransformerInterface $transformer) => $transformer->getObjectClass(), $this->transformers));
    }

    public function getIdentifierField(): string
    {
        return array_map(fn (ElasticaToModelTransformerInterface $transformer) => $transformer->getIdentifierField(), $this->transformers)[0];
    }

    public function transform(array $elasticaObjects): array
    {
        $sorted = [];
        foreach ($elasticaObjects as $object) {
            $sorted[$object->getIndex()][] = $object;
        }

        $transformed = [];
        foreach ($sorted as $type => $objects) {
            $transformedObjects = $this->transformers[$type]->transform($objects);
            $identifierGetter = 'get'.ucfirst($this->transformers[$type]->getIdentifierField());
            $transformed[$type] = array_combine(
                array_map(
                    fn ($o) => $o->$identifierGetter(),
                    $transformedObjects
                ),
                $transformedObjects
            );
        }

        $result = [];
        foreach ($elasticaObjects as $object) {
            if (array_key_exists((string) $object->getId(), $transformed[$object->getIndex()])) {
                $result[] = $transformed[$object->getIndex()][(string) $object->getId()];
            }
        }

        return $result;
    }

    public function hybridTransform(array $elasticaObjects): array
    {
        $objects = $this->transform($elasticaObjects);

        $result = [];
        for ($i = 0, $j = count($elasticaObjects); $i < $j; ++$i) {
            if (!isset($objects[$i])) {
                continue;
            }
            $result[] = new HybridResult($elasticaObjects[$i], $objects[$i]);
        }

        return $result;
    }
}
