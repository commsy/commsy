<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 22.08.18
 * Time: 08:01
 */

namespace CommsyBundle\Twig\Extension;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Twig\Extension\AbstractExtension;

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
            new \Twig_SimpleFunction('pageTitle', [$this, 'pageTitle']),
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