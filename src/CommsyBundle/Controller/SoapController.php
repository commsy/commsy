<?php
namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Zend\Soap;

class SoapController extends Controller
{
    /**
     * @Route("/api/soap")
     */
    public function soapAction(Request $request)
    {
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
        $uri = $this->generateUrl('commsy_soap_soap', [], true);

        // auto discover
        $autoDiscover = new Soap\AutoDiscover();
        $autoDiscover->setClass($);
        $autoDiscover->setUri($uri);

        $wsdl = $autoDiscover->generate();

        // response
        $response = new Response();

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setCharset('UTF-8'); //ISO-8859-1

        $response->setContent($wsdl->toXml());

        return $response;
    }

    private function handleSOAP()
    {
        $uri = $this->generateUrl('commsy_soap_soap', [], true);

        $soapServer = new Soap\Server(null, [
            'location' => $uri,
            'uri' => $uri,
        ]);
        $soapServer->setClass($);

        // response
        $response = new Response();


        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setCharset('UTF-8'); //ISO-8859-1

        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}