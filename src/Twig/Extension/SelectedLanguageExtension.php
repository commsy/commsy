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

namespace App\Twig\Extension;

use App\Services\LegacyEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Returns the language selected for the current context or user.
 */
class SelectedLanguageExtension extends AbstractExtension
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('selectedLanguage', [$this, 'selectedLanguage']),
        ];
    }

    public function selectedLanguage()
    {
        $language = $this->legacyEnvironment->getSelectedLanguage();

        return $language;
    }
}
