<?php

namespace CommsyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Liip\ThemeBundle\ActiveTheme;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

class CommsyRequestListener
{
    private $roomService;

    private $activeTheme;

    private $themeArray;

    private $legacyEnvironment;

    public function __construct(RoomService $roomService, ActiveTheme $activeTheme, $themeArray, LegacyEnvironment $legacyEnvironment)
    {
        $this->roomService = $roomService;
        $this->activeTheme = $activeTheme;
        $this->themeArray = $themeArray;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // get the current room theme and set it as active for LiipThemeBundle
        $roomItem = $this->roomService->getCurrentRoomItem();

        if ($roomItem) {
            $colorArray = $roomItem->getColorArray();
            if ($colorArray) {
                if (isset($colorArray['schema'])) {
                    $schema = $colorArray['schema'];

                    if (in_array($schema, $this->themeArray)) {
                        $this->activeTheme->setName($schema);
                    }
                }
            }
        }
        
        $this->updateUserLogin();
        
        $this->logRequest();
    }
    
    private function updateUserLogin () {
        $user = $this->legacyEnvironment->getEnvironment()->getCurrentUser();
        if ($user->isUser() and !$user->isRoot()) {
            $user->updateLastLogin();
        }
    }
    
    private function logRequest () {
        $environment = $this->legacyEnvironment->getEnvironment();
        $l_current_user = $environment->getCurrentUserItem();
        
        if ($l_current_user->isUser() && !$l_current_user->isRoot()) {
            $array = array();
            if ( isset($_GET['iid']) ) {
               $array['iid'] = $_GET['iid'];
            } elseif ( isset($_POST['iid']) ) {
               $array['iid'] = $_POST['iid'];
            }
            if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
               $array['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } else {
               $array['user_agent'] = 'No Info';
            }
            
            if ( isset($_POST) ) {
               $post_content = array2XML($_POST);
            } else {
               $post_content = '';
            }
            $current_context = $environment->getCurrentContextItem();
            $server_item = $environment->getServerItem();
            //Datenschutz
            //if($current_context->withLogIPCover() or $server_item->withLogIPCover()){
            if($server_item->withLogIPCover()){
            	// if datasecurity is active dont show last two fields
            	$remote_adress_array = explode('.', $_SERVER['REMOTE_ADDR']);
            	$array['remote_addr']	   = $remote_adress_array['0'].'.'.$remote_adress_array['1'].'.'.$remote_adress_array['2'].'.XXX';
            } else {
            	$array['remote_addr']      = $_SERVER['REMOTE_ADDR'];
            }
            unset($server_item);
            unset ($current_context);
            
            $array['script_name']      = $_SERVER['SCRIPT_NAME'];
            $array['query_string']     = str_ireplace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
            $array['request_method']   = $_SERVER['REQUEST_METHOD'];
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
            $unique = count(array_unique($sql_query_array));
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