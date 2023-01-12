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

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonHTMLResponse extends JsonResponse
{
    public function __construct($html, $status = 200, $headers = [])
    {
        $data = [
            'html' => $html,
        ];

        parent::__construct($data, $status, $headers, false);
    }
}
