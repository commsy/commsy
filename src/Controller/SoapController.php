<?php
namespace App\Controller;

use App\Services\SoapService;
use Laminas\Soap\AutoDiscover;
use Laminas\Soap\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SoapController extends AbstractController
{
    /**
     * @Route("/api/soap")
     * @param Request $request
     * @return Response
     */
    public function soapAction(
        Request $request
    ) {
        if ($request->query->has('wsdl')) {
            // handle wsdl request
            return $this->handleWSDL();
        } else {
            // handle soap request
            return $this->handleSOAP();
        }
    }

    private function handleWSDL()
    {
        $uri = $this->generateUrl('app_soap_soap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // auto discover
        $autoDiscover = new AutoDiscover();
        $autoDiscover->setClass(SoapService::class);
        $autoDiscover->setUri($uri);

        $wsdl = $autoDiscover->generate();

        // response
        $response = new Response();

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setCharset('UTF-8');

        $response->setContent($wsdl->toXml());

        return $response;
    }

    private function handleSOAP()
    {
        $uri = $this->generateUrl('app_soap_soap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $soapServer = new Server(null, [
            'location' => $uri,
            'uri' => $uri,
        ]);
        $soapServer->setClass(SoapService::class);

        // response
        $response = new Response();

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setCharset('UTF-8');

        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}