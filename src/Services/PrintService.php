<?php

namespace App\Services;

use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LegacyEnvironment;

/**
 * Class PrintService
 *
 * @package App\Services
 */
class PrintService
{    
    private $legacyEnvironment;
    private $pdf;

    private $proxyIp;
    private $proxyPort;
    
    public function __construct(LegacyEnvironment $legacyEnvironment, Pdf $pdf, $proxyIp, $proxyPort)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->pdf = $pdf;
        $this->proxyIp = $proxyIp;
        $this->proxyPort = $proxyPort;
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
     * @param string $html HTML content
     * @param bool $debug Return plain html, instead of a pdf document (helps debugging)
     *
     * @return Response HTML Response containing the generated PDF
     */
    public function buildPdfResponse($html, $debug = true)
    {
        if ($debug) {
            return new Response($html);
        }

        return new Response($this->getPdfContent($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="print.pdf"',
        ]);
    }

    /**
     * Sets wkhtmltopdf command line options
     */
    private function setOptions() {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();

        $this->pdf->setOptions([
            'footer-line' => true,
            'footer-spacing' => 1,
            'footer-center' => '[page] / [toPage]',
            'header-line' => true,
            'header-spacing' => 1,
            'header-right' => date('d.m.y'),
            'header-left' => $roomItem->getTitle(),
            'header-center' => 'CommSy',
            'images' => true,
            'load-media-error-handling' => 'ignore',
            'load-error-handling' => 'ignore',
        ]);

        // proxy support
        if ($this->proxyIp && $this->proxyPort) {
            $proxy = 'http://' . $this->proxyIp . ':' . $this->proxyPort;

            $this->pdf->setOption('proxy', $proxy);
        }
        
        // set cookie for authentication - needed to request images
        $this->pdf->setOption('cookie', [
            'SID' => $this->legacyEnvironment->getSessionID(),
        ]);
    }
}