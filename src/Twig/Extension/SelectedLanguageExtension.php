<?php

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
