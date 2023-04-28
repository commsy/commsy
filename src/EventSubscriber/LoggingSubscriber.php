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

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class LoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RoomService $roomService, private readonly LegacyEnvironment $legacyEnvironment)
    {
    }

    public function onTerminateEvent(TerminateEvent $event)
    {
        $array = [];
        if ($event->isMainRequest()) {
            $request = $event->getRequest();

            /*
               restrict logging to the following requests:
               "/room/<id>"
               "/room/<id>/<rubric>"
               "/room/<id>/<rubric>/<id>"
               "/dashboard/<id>"
            */
            $logRequest = false;
            if (preg_match('~\/room\/(\d)+$~', $request->getUri())) {
                $logRequest = true;
            } elseif (preg_match('~\/room\/(\d)+\/([a-z])+$~', $request->getUri())) {
                $logRequest = true;
            } elseif (preg_match('~\/room\/(\d)+\/([a-z])+\/(\d)+$~', $request->getUri())) {
                $logRequest = true;
            } elseif (preg_match('~\/dashboard\/(\d)+$~', $request->getUri())) {
                $logRequest = true;
            }

            if ($logRequest) {
                $environment = $this->legacyEnvironment->getEnvironment();
                $l_current_user = $environment->getCurrentUserItem();

                $array['user_agent'] = $request->headers->get('User-Agent', 'No Info');

                if (isset($_POST)) {
                    $post_content = array2XML($_POST);
                } else {
                    $post_content = '';
                }
                $current_context = $environment->getCurrentContextItem();
                $server_item = $environment->getServerItem();

                // Datenschutz
                if ($request->server->has('REMOTE_ADDR')) {
                    $remoteAddress = $request->server->get('REMOTE_ADDR');
                    $array['remote_addr'] = $remoteAddress;
                }

                $array['script_name'] = $request->getScriptName();
                $array['query_string'] = str_ireplace($request->getScriptName(), '', $request->getRequestUri());
                $array['request_method'] = $request->getMethod();
                $array['post_content'] = $post_content;
                if (!empty($l_current_user)) {
                    $array['user_item_id'] = $l_current_user->getItemID();
                    $array['user_user_id'] = $l_current_user->getUserID();
                }
                $array['context_id'] = $environment->getCurrentContextID();
                $array['module'] = $environment->getCurrentModule();
                $array['function'] = $environment->getCurrentFunction();
                $array['parameter_string'] = $environment->getCurrentParameterString();

                $db_connector = $environment->getDBConnector();
                $sql_query_array = $db_connector->getQueryArray();
                $all = is_countable($sql_query_array) ? count($sql_query_array) : 0;
                $array['queries'] = $all;

                if (isset($time_start)) {
                    $time_end = getmicrotime();
                    $time = round($time_end - $time_start, 3);
                    $array['time'] = $time;
                } else {
                    $array['time'] = 0;
                }

                $log_manager = $environment->getLogManager();
                $log_manager->saveArray($array);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TerminateEvent::class => 'onTerminateEvent',
        ];
    }
}
