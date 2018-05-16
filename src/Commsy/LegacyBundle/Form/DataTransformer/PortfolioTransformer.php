<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class PortfolioTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_portfolio_item object to an array
     *
     * @param cs_portfolio_item $groupItem
     * @return array
     */
    public function transform($portfolioItem)
    {
        $portfolioData = array();

        if ($portfolioItem) {
            $portfolioData['title'] = html_entity_decode($portfolioItem->getTitle());
            $portfolioData['description'] = html_entity_decode($portfolioItem->getDescription());
        }

        return $portfolioData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $portfolioObject
     * @param array $portfolioData
     * @return cs_portfolio_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($portfolioObject, $portfolioData)
    {
        $portfolioObject->setTitle($portfolioData['title']);
        $portfolioObject->setDescription($portfolioData['description']);
        return $portfolioObject;
    }
}