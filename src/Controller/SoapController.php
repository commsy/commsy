<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
// $plugin_config_file = 'etc/commsy/plugin.php';
// $soap_functions_array = array();
// if ( file_exists($plugin_config_file) ) {
//   include_once($plugin_config_file);
//   include_once('etc/cs_constants.php');
//   include_once('etc/cs_config.php');
//   include_once('functions/misc_functions.php');
//   include_once('classes/cs_environment.php');
//   $environment = new cs_environment();
  
//   if ( !empty($_GET['plugin']) ) {
//     // full wsdl only for plugin
//     $plugin_name = $_GET['plugin'];
//     $plugin_class = $environment->getPluginClass($plugin_name);
//     if ( !empty($plugin_class)
//           and method_exists($plugin_class, 'getFullWSDL') 
//        ) {
//         $wsdl = $plugin_class->getFullWSDL();
//         unset($plugin_class);
//         if ( !empty($wsdl) ) {
//            echo($wsdl);
//            exit();
//         }
//     }
//   } else {
//     // merge plugin soap functions into CommSy soap functions
//      $soap_functions_array = plugin_hook_output_all('getSOAPAPIArray',array(),'ARRAY');
//   }
// }

// // soap_functions from classes
// if ( !isset($environment) ) {
// include_once('etc/cs_constants.php');
// include_once('etc/cs_config.php');
// include_once('functions/misc_functions.php');
// include_once('classes/cs_environment.php');
// $environment = new cs_environment();
// }

// $connection_obj = $environment->getCommSyConnectionObject();
// if ( !empty($connection_obj) ) {
// $soap_functions_array_from_class = $connection_obj->getSoapFunctionArray();
// if ( !empty($soap_functions_array_from_class) ) {
//    $soap_functions_array = array_merge($soap_functions_array,$soap_functions_array_from_class);
// }
// }
        $uri = $this->generateUrl('app_soap_soap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // auto discover
        $autoDiscover = new Soap\AutoDiscover();
        $autoDiscover->setClass($this->get('commsy.api.soap'));
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
// if ( !empty($_GET['plugin']) ) {
//    $plugin_config_file = 'etc/commsy/plugin.php';
//    if ( file_exists($plugin_config_file) ) {
//       include_once($plugin_config_file);
//       $plugin_name = $_GET['plugin'];
//       $plugin_class = $environment->getPluginClass($plugin_name);
//       if ( !empty($plugin_class)
//           and method_exists($plugin_class, 'getURIforSoapServer') 
//         ) {
//          $uri_server = $plugin_class->getURIforSoapServer();
//          unset($plugin_class);
//          if ( !empty($uri_server) ) {
//             $uri = $uri_server;
//          }
//       }
//    }
// }

        $uri = $this->generateUrl('app_soap_soap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $soapServer = new Soap\Server(null, [
            'location' => $uri,
            'uri' => $uri,
        ]);
        $soapServer->setClass($this->get('commsy.api.soap'));

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