<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 21.06.18
 * Time: 16:50
 */

namespace App\CalDAV;


use Sabre\DAV\Exception;
use Sabre\DAV\Server as BaseServer;
use Symfony\Component\HttpFoundation\Response;

class Server extends BaseServer
{
    /**
     * @inheritdoc
     * @throws Exception
     */
    public function __construct($treeOrNode = null)
    {
        parent::__construct($treeOrNode);

        $this->sapi = new Sapi();
    }

    /**
     * Starts the DAV server
     *
     * @return Response
     */
    public function exec()
    {
        parent::exec();

        return Sapi::$symfonyResponse;
    }
}