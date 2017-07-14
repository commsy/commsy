<?php

namespace CommsyBundle\CalDAV;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* This is an authentication backend that uses a database to manage passwords.
*
* @copyright Copyright (C) fruux GmbH (https://fruux.com/)
* @author Evert Pot (http://evertpot.com/)
* @license http://sabre.io/license/ Modified BSD License
*/
class PrincipalPDO extends \Sabre\DAVACL\PrincipalBackend\PDO {

    private $container;
    private $portalId;

    /**
    * Reference to PDO connection
    *
    * @var AuthPDO
    */
    protected $pdo;


    /**
    * Creates the backend object.
    *
    * If the filename argument is passed in, it will parse out the specified file fist.
    *
    * @param \PDO $pdo
    */
    function __construct(\PDO $pdo, ContainerInterface $container, $portalId) {
        $this->pdo = $pdo;
        $this->container = $container;
        $this->portalId = $portalId;
    }

}
