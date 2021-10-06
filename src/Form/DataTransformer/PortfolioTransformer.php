<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_portfolio_manager;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PortfolioTransformer extends AbstractTransformer
{
    protected $entity = 'portfolio';

    /**
     * @var cs_portfolio_manager
     */
    private cs_portfolio_manager $portfolioManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->portfolioManager = $legacyEnvironment->getPortfolioManager();
    }

    /**
     * Transforms a cs_portfolio_item object to an array
     *
     * @param \cs_portfolio_item $groupItem
     * @return array
     */
    public function transform($portfolioItem)
    {
        $portfolioData = array();

        if ($portfolioItem) {
            $portfolioData['title'] = html_entity_decode($portfolioItem->getTitle());
            $portfolioData['description'] = html_entity_decode($portfolioItem->getDescription());
            $portfolioData['is_template'] = $portfolioItem->isTemplate();

            $externalTemplate = $this->portfolioManager->getExternalTemplate($portfolioItem->getItemId());
            $portfolioData['external_template'] = implode(";", $externalTemplate);

            $externalViewer = $this->portfolioManager->getExternalViewer($portfolioItem->getItemId());
            $portfolioData['external_viewer'] = implode(";", $externalViewer);
        }

        return $portfolioData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $portfolioObject
     * @param array $portfolioData
     * @return \cs_portfolio_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($portfolioObject, $portfolioData)
    {
        $portfolioObject->setTitle($portfolioData['title']);
        $portfolioObject->setDescription($portfolioData['description']);

        if ($portfolioData['is_template']) {
            $portfolioObject->setTemplate();
        } else {
            $portfolioObject->unsetTemplate();
        }

        $externalTemplateUserIds = explode(";", trim($portfolioData['external_template']));
        $portfolioObject->setExternalTemplate($externalTemplateUserIds);

        $externalViewerUserIds = explode(";", trim($portfolioData['external_viewer']));
        $portfolioObject->setExternalViewer($externalViewerUserIds);

        return $portfolioObject;
    }
}