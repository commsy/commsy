<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class LoggingSubscriber implements EventSubscriberInterface
{
    private $roomService;

    private $legacyEnvironment;

    public function __construct(RoomService $roomService, LegacyEnvironment $legacyEnvironment)
    {
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function onTerminateEvent(TerminateEvent $event)
    {
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
            } else if (preg_match('~\/room\/(\d)+\/([a-z])+$~', $request->getUri())) {
                $logRequest = true;
            } else if (preg_match('~\/room\/(\d)+\/([a-z])+\/(\d)+$~', $request->getUri())) {
                $logRequest = true;
            } else if (preg_match('~\/dashboard\/(\d)+$~', $request->getUri())) {
                $logRequest = true;
            }

            if ($logRequest) {
                $environment = $this->legacyEnvironment->getEnvironment();
                $l_current_user = $environment->getCurrentUserItem();

                $array['user_agent'] = $request->headers->get('User-Agent', 'No Info');

                if ( isset($_POST) ) {
                    $post_content = array2XML($_POST);
                } else {
                    $post_content = '';
                }
                $current_context = $environment->getCurrentContextItem();
                $server_item = $environment->getServerItem();

                // Datenschutz
                if ($request->server->has('REMOTE_ADDR')) {
                    $remoteAddress = $request->server->get('REMOTE_ADDR');

                    if ($server_item->withLogIPCover()) {
                        // if datasecurity is active dont show last two fields
                        $remote_adress_array = explode('.', $remoteAddress);
                        $array['remote_addr']	   = $remote_adress_array['0'].'.'.$remote_adress_array['1'].'.'.$remote_adress_array['2'].'.XXX';
                    } else {
                        $array['remote_addr']      = $remoteAddress;
                    }
                }

                $array['script_name']      = $request->getScriptName();
                $array['query_string']     = str_ireplace($request->getScriptName(), '', $request->getRequestUri());
                $array['request_method']   = $request->getMethod();
                $array['post_content']     = $post_content;
                if ( !empty($l_current_user) ) {
                    $array['user_item_id']     = $l_current_user->getItemID();
                    $array['user_user_id']     = $l_current_user->getUserID();
                }
                $array['context_id']       = $environment->getCurrentContextID();
                $array['module']           = $environment->getCurrentModule();
                $array['function']         = $environment->getCurrentFunction();
                $array['parameter_string'] = $environment->getCurrentParameterString();

                $db_connector = $environment->getDBConnector();
                $sql_query_array = $db_connector->getQueryArray();
                $all = count($sql_query_array);
                $array['queries'] = $all;

                if(isset($time_start)){
                    $time_end = getmicrotime();
                    $time = round($time_end - $time_start,3);
                    $array['time'] = $time;
                } else {
                    $array['time'] = 0;
                }

                $log_manager = $environment->getLogManager();
                $log_manager->saveArray($array);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            TerminateEvent::class => 'onTerminateEvent',
        ];
    }
}
