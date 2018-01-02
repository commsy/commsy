<?php
    require_once('classes/controller/cs_ajax_controller.php');
    require_once('functions/security_functions');

    class cs_ajax_mdo_perform_search_controller extends cs_ajax_controller {
        /**
         * constructor
         */
        public function __construct(cs_environment $environment) {
            // call parent
            parent::__construct($environment);
        }

        /*
         * every derived class needs to implement an processTemplate function
         */
        public function process() {
            // TODO: check for rights, see cs_ajax_accounts_controller

            // call parent
            parent::process();
        }

        /*
         * updates the editing date of an item
         */
        public function actionSearch() {
            $environment = $this->_environment;

            include_once('functions/development_functions.php');

            $access = false;

            // check for rights for mdo
            $current_context_item = $environment->getCurrentContextItem();
            if($current_context_item->isProjectRoom()) {
                // does this project room has any community room?
                $community_list = $current_context_item->getCommunityList();
                if($community_list->isNotEmpty()) {
                    // check for community rooms activated the mdo feature
                    $community = $community_list->getFirst();
                    while($community) {
                        $mdo_active = $community->getMDOActive();
                        if(!empty($mdo_active) && $mdo_active != '-1') {
                            // mdo access granted, get content from Mediendistribution-Online
                            $access = true;
                            $community_room = $community;

                            // stop searching here
                            break;
                        }
                        $community = $community_list->getNext();
                    }
                }
            }

            if($access === true) {
                global $c_media_integration_url;
                global $c_media_integration_pw;
                // $curl_handler = curl_init('http://arix.datenbank-bildungsmedien.net/HH');
                if ($community_room->getMDOKey()) {
                    $key = $community_room->getMDOKey();
                    $curl_handler = curl_init($c_media_integration_url.$key);
                } else {
                    $curl_handler = curl_init($c_media_integration_url);
                }
                
                curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_handler, CURLOPT_POST, true);

                ############################
                ## 1. CommSy -> Arix: <notch type='commsy' />
                ## 2. Arix -> CommSy: <notch id='SESSION_ID'>NOTCH</notch> 
                ############################
                $data = '<notch type="commsy" />';
                curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array('xmlstatement' => $data));
                $response = curl_exec($curl_handler);
                if(!$response) {
                    $this->setErrorReturn("1234", "No response", "Detail");
                } else {
                    $xml_object = simplexml_load_string($response);
                    $result = $xml_object->xpath('/notch[@id]');
                    $session_id = (string) $result[0]->attributes()->id;
                    $notch = (string) $result[0];
                    unset($xml_object);

                    $id = mysql_escape_mimic($_GET['identifier']);
                    $data = "<notch identifier='".$id."' />";

                    curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array('xmlstatement' => $data));
                    $response = curl_exec($curl_handler);
                    if(!$response) {
                        $this->setErrorReturn("1234", "No response search", "Detail");
                    } else {
                        $xml_object = simplexml_load_string($response);
                        $result = $xml_object->xpath('/notch[@id]');
                        $notch_id = (string) $result[0]->attributes()->id;
                        $notch = (string) $result[0];
                        unset($xml_object);

                        $newNotchId = md5($notch.':'.$c_media_integration_pw);

                        $data = "<link id='".$notch_id."'>".$newNotchId."</link>";

                        // get media
                        curl_setopt($curl_handler, CURLOPT_POSTFIELDS, array('xmlstatement' => $data));
                        $response = curl_exec($curl_handler);
                        if(!$response) {
                            $this->setErrorReturn("1234", "No response search", "Detail");
                        } else {
                            $xml_object = simplexml_load_string($response);
                            $result = $xml_object->xpath("/link/a[@href][text()='direct']");
                            $url = (string) $result[0]->attributes()->href;
                            $retour = array('url' => $url);

                            unset($xml_object);
                            $this->setSuccessfullDataReturn($retour);
                            
                        }

                    }

                }
            } else {
                $this->setErrorReturn("1234", "Error", "No access");
                // $page->add('success', 'false');
            }
            
            echo $this->_return;
        }
    }