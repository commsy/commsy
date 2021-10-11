<?php

namespace App\Services;

use cs_environment;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LegacyEnvironment;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class PrintService
 *
 * @package App\Services
 */
class PrintService
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Pdf
     */
    private Pdf $pdf;

    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * @var string
     */
    private string $proxyIp;

    /**
     * @var string
     */
    private string $proxyPort;
    
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Pdf $pdf,
        SessionInterface $session,
        string $proxyIp,
        string $proxyPort
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->pdf = $pdf;
        $this->session = $session;
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
     * @param bool $debug Return plain html, instead of a pdf document (helps debugging); defaults to false
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

        return new Response($this->getPdfContent($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Sets wkhtmltopdf command line options
     */
    private function setOptions() {
        $roomItem = $this->legacyEnvironment->getCurrentContextItem();
        if ($roomItem->getRoomType() === CS_PRIVATEROOM_TYPE) {
            $roomItem = $this->legacyEnvironment->getCurrentPortalItem();
        }

        if($this->legacyEnvironment->getSelectedLanguage() == 'en'){
            $dateFormat = 'm/d/y';
        }else{
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
            'images' => true,
            'load-media-error-handling' => 'ignore',
            'load-error-handling' => 'ignore',
            'disable-javascript' => true,
        ]);

        // proxy support
        if ($this->proxyIp && $this->proxyPort) {
            $proxy = 'http://' . $this->proxyIp . ':' . $this->proxyPort;

            $this->pdf->setOption('proxy', $proxy);
        }

        // set cookie for authentication - needed to request images
        $this->pdf->setOption('cookie', [
            'PHPSESSID' => $this->session->getId()
        ]);
    }
}