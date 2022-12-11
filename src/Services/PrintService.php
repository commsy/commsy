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

namespace App\Services;

use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class PrintService.
 */
class PrintService
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private Pdf $pdf,
        private SessionInterface $session,
        private string $proxyIp,
        private string $proxyPort
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Converts the given HTML content into a pdf.
     *
     * @param string $html The HTML content
     *
     * @return string Generated PDF content
     */
    public function getPdfContent($html)
    {
        $this->setOptions();

        return $this->pdf->getOutputFromHtml($html);
    }

    /**
     * Generates a pdf response, converting the given html content.
     *
     * @param string $html     HTML content
     * @param bool   $debug    Return plain html, instead of a pdf document (helps debugging); defaults to false
     * @param string $fileName the file name for the generated PDF document; defaults to "print.pdf"
     *
     * @return Response HTML Response containing the generated PDF
     */
    public function buildPdfResponse($html, $debug = false, $fileName = 'print.pdf')
    {
        if ($debug) {
            return new Response($html);
        }

        // escape any quotes or backslashes in the file name
        // NOTE: with >=PHP7.3, we could also use `filter_var($fileName, FILTER_SANITIZE_ADD_SLASHES)`;
        $fileName = addslashes($fileName);

        return new Response($this->getPdfContent($html), \Symfony\Component\HttpFoundation\Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Sets wkhtmltopdf command line options.
     */
    private function setOptions()
    {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();
        if (CS_PRIVATEROOM_TYPE === $roomItem->getRoomType()) {
            $roomItem = $this->legacyEnvironment->getCurrentPortalItem();
        }

        if ('en' == $this->legacyEnvironment->getSelectedLanguage()) {
            $dateFormat = 'm/d/y';
        } else {
            $dateFormat = 'd.m.y';
        }

        $this->pdf->setOptions([
            'footer-line' => true,
            'footer-spacing' => 1,
            'footer-center' => '[page] / [toPage]',
            'header-line' => true,
            'header-spacing' => 1,
            'header-right' => date($dateFormat),
            'header-left' => $roomItem->getTitle(),
            'header-center' => 'CommSy',
            'no-images' => true,
            'load-media-error-handling' => 'ignore',
            'load-error-handling' => 'ignore',
            'disable-javascript' => true,
        ]);

        // proxy support
        if ($this->proxyIp && $this->proxyPort) {
            $proxy = 'http://'.$this->proxyIp.':'.$this->proxyPort;

            $this->pdf->setOption('proxy', $proxy);
        }

        // set cookie for authentication - needed to request images
        $this->pdf->setOption('cookie', [
            'PHPSESSID' => $this->session->getId(),
        ]);
    }
}
