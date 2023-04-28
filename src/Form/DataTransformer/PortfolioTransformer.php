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

namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_portfolio_item;
use cs_portfolio_manager;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PortfolioTransformer extends AbstractTransformer
{
    protected $entity = 'portfolio';

    private readonly cs_portfolio_manager $portfolioManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->portfolioManager = $legacyEnvironment->getEnvironment()->getPortfolioManager();
    }

    /**
     * Transforms a cs_portfolio_item object to an array.
     *
     * @param cs_portfolio_item $groupItem
     *
     * @return array
     */
    public function transform($portfolioItem)
    {
        $portfolioData = [];

        if ($portfolioItem) {
            $portfolioData['title'] = html_entity_decode((string) $portfolioItem->getTitle());
            $portfolioData['description'] = html_entity_decode((string) $portfolioItem->getDescription());
            $portfolioData['is_template'] = $portfolioItem->isTemplate();

            $externalTemplate = $this->portfolioManager->getExternalTemplate($portfolioItem->getItemId());
            $portfolioData['external_template'] = implode(';', $externalTemplate);

            $externalViewer = $this->portfolioManager->getExternalViewer($portfolioItem->getItemId());
            $portfolioData['external_viewer'] = implode(';', $externalViewer);
        }

        return $portfolioData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $portfolioObject
     * @param array  $portfolioData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($portfolioObject, $portfolioData): cs_portfolio_item
    {
        $portfolioObject->setTitle($portfolioData['title']);
        $portfolioObject->setDescription($portfolioData['description']);

        if ($portfolioData['is_template']) {
            $portfolioObject->setTemplate();
        } else {
            $portfolioObject->unsetTemplate();
        }

        $externalTemplateUserIds = explode(';', trim((string) $portfolioData['external_template']));
        $portfolioObject->setExternalTemplate($externalTemplateUserIds);

        $externalViewerUserIds = explode(';', trim((string) $portfolioData['external_viewer']));
        $portfolioObject->setExternalViewer($externalViewerUserIds);

        return $portfolioObject;
    }
}
