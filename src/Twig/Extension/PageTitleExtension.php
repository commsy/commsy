<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 22.08.18
 * Time: 08:01
 */

namespace App\Twig\Extension;


use App\Services\LegacyEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageTitleExtension extends AbstractExtension
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
            new TwigFunction('pageTitle', [$this, 'pageTitle']),
        ];
    }

    public function pageTitle()
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            return $portal->getTitle();
        }

        return 'CommSy';
    }
}