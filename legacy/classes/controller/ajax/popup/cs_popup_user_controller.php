<?php
require_once('classes/controller/ajax/popup/cs_rubric_popup_controller.php');

class cs_popup_user_controller implements cs_rubric_popup_controller {
    private $_environment = null;
    private $_popup_controller = null;

    /**
     * constructor
     */
    public function __construct(cs_environment $environment, cs_ajax_popup_controller $popup_controller) {
        $this->_environment = $environment;
        $this->_popup_controller = $popup_controller;
    }

    public function initPopup($item, $data) {
			// assign template vars
			$this->assignTemplateVars();
			$current_context = $this->_environment->getCurrentContextItem();

			if($item !== null) {
				// edit mode

				// TODO: check rights

				$this->_popup_controller->assign('item', 'title', $item->getTitle());

				$this->_popup_controller->assign('item', 'description', $item->getDescription());
				
				$this->_popup_controller->assign('item', 'want_mail_get_account', $item->getAccountWantMail());
				$this->_popup_controller->assign('item', 'mail_delete_entry', $item->getDeleteEntryWantMail());
				$this->_popup_controller->assign('item', 'want_mail_open_room', $item->getOpenRoomWantMail());
				$this->_popup_controller->assign('item', 'want_mail_publish_material', $item->getPublishMaterialWantMail());
				$this->_popup_controller->assign('item', 'language', $item->getLanguage());
				$this->_popup_controller->assign('item', 'commsy_visible', $item->getVisible());
			    $this->_popup_controller->assign('item', 'picture', $item->getPicture());

			    $this->_popup_controller->assign('item', 'birthday', $item->getBirthday());
			    $this->_popup_controller->assign('item', 'email_visibility', $item->isEmailVisible());
			    $this->_popup_controller->assign('item', 'email', $item->getEmail());
			    $this->_popup_controller->assign('item', 'telephone', $item->getTelephone());
			    $this->_popup_controller->assign('item', 'cellularphone', $item->getCellularphone());
			    $this->_popup_controller->assign('item', 'street', $item->getStreet());
			    $this->_popup_controller->assign('item', 'zipcode', $item->getZipCode());
			    $this->_popup_controller->assign('item', 'city', $item->getCity());
			    $this->_popup_controller->assign('item', 'room', $item->getRoom());

			    $this->_popup_controller->assign('item', 'organisation', $item->getOrganisation());
			    $this->_popup_controller->assign('item', 'position', $item->getPosition());
			    $this->_popup_controller->assign('item', 'homepage', $item->getHomepage());

			    $this->_popup_controller->assign('item', 'icq', $item->getICQ());
			    $this->_popup_controller->assign('item', 'yahoo', $item->getYahoo());
			    $this->_popup_controller->assign('item', 'msn', $item->getMSN());
			    $this->_popup_controller->assign('item', 'skype', $item->getSkype());

			}
    }

    public function save($form_data, $additional = array()) {
        $environment = $this->_environment;
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        if(isset($additional['action']) && $additional['action'] === 'upload_picture') $current_iid = $additional['iid'];
        else $current_iid = $form_data['iid'];

        $translator = $this->_environment->getTranslationObject();

        if($current_iid === 'NEW') {
            $user_item = null;
        } else {
            $user_manager = $this->_environment->getUserManager();
            $user_item = $user_manager->getItem($current_iid);
        }

        $this->_popup_controller->performChecks($user_item, $form_data, $additional);

        // TODO: check rights */
		/****************************/
        if ( $current_iid != 'NEW' and !isset($user_item) ) {

        } elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
        ($current_iid != 'NEW' and isset($user_item) and
        $user_item->mayEdit($current_user))) ) {

		/****************************/


        } else { //Acces granted
			$this->cleanup_session($current_iid);

			// upload picture
			if(isset($additional['action']) && $additional['action'] === 'upload_picture') {
				if($this->_popup_controller->checkFormData('file_upload')) {

					/* handle picture upload */
					if(!empty($additional["fileInfo"])) {
						$srcfile = $additional["fileInfo"]["file"];
						$targetfile = $srcfile . "_converted";

						$session = $this->_environment->getSessionItem();
						$session->unsetValue("add_files");

						// resize image to a maximum width of 150px and keep ratio
			            $size = getimagesize($srcfile);
			            $x_orig= $size[0];
			            $y_orig= $size[1];
			            //$verhaeltnis = $x_orig/$y_orig;
			            $verhaeltnis = $y_orig/$x_orig;
			            $max_width = 150;
			            //$ratio = 1.618; // Goldener Schnitt
			            //$ratio = 1.5; // 2:3
			            $ratio = 1.334; // 3:4
			            //$ratio = 1; // 1:1
			            if($verhaeltnis < $ratio){
			               // Breiter als 1:$ratio
			               $source_width = ($size[1] * $max_width) / ($max_width * $ratio);
			               $source_height = $size[1];
			               $source_x = ($size[0] - $source_width) / 2;
			               $source_y = 0;
			            } else {
			               // Höher als 1:$ratio
			               $source_width = $size[0];
			               $source_height = ($size[0] * ($max_width * $ratio)) / ($max_width);
			               $source_x = 0;
			               $source_y = ($size[1] - $source_height) / 2;
			            }
			            switch ($size[2]) {
			                  case '1':
			                     $im = imagecreatefromgif($srcfile);
			                     break;
			                  case '2':
			                     $im = imagecreatefromjpeg($srcfile);
			                     break;
			                  case '3':
			                     $im = imagecreatefrompng($srcfile);
			                     break;
			            }
		                $newimg = imagecreatetruecolor($max_width,($max_width * $ratio));
		                imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
		                imagepng($newimg,$targetfile);
		                imagedestroy($im);
		                imagedestroy($newimg);

						// determ new file name
						$filename_info = pathinfo($targetfile);
						$filename = 'cid' . $this->_environment->getCurrentContextID() . '_' . $user_item->getUserID();
						// . '_'. $additional["fileInfo"]["name"];
						// copy file and set picture
						$disc_manager = $this->_environment->getDiscManager();

						$disc_manager->copyFile($targetfile, $filename, true);
						$user_item->setPicture($filename);
						$user_item->save();

						// set return
               			$this->_popup_controller->setSuccessfullDataReturn($filename);
					}
				}
			} else {
				// save item
				if($this->_popup_controller->checkFormData('basic')) {
	                $session = $this->_environment->getSessionItem();
	                $item_is_new = false;

	                // Set modificator and modification date
	                $current_user = $environment->getCurrentUserItem();
	                $user_item->setModificatorItem($current_user);

	                // Set attributes
	                if ( isset($form_data['title']) ) {
	                    $user_item->setTitle($form_data['title']);
	                }

		            if (isset($form_data['email_visibility']) and !empty($form_data['email_visibility'])) {
		               $user_item->setEmailNotVisible();
		            } else {
		               $user_item->setEmailVisible();
		            }

	                if ( isset($form_data['description']) ) {
	                    $user_item->setDescription($this->_popup_controller->getUtils()->cleanCKEditor($form_data['description']));
	                }

		            if ( !empty($form_data['commsy_visible']) ) {
		               if ($form_data['commsy_visible'] == 1) {
		                  $user_item->setVisibleToLoggedIn();
		               } elseif ($form_data['commsy_visible'] == 2) {
		                  $user_item->setVisibleToAll();
		               }
		            }

		            if (isset($form_data['telephone'])) {
		               $user_item->setTelephone($form_data['telephone']);
		            }
		            if (isset($form_data['birthday'])) {
		               $user_item->setBirthday($form_data['birthday']);
		            }
		            if (isset($form_data['cellularphone'])) {
		               $user_item->setCellularphone($form_data['cellularphone']);
		            }
		            if (isset($form_data['homepage'])) {
		               $user_item->setHomepage($form_data['homepage']);
		            }
		            if (isset($form_data['organisation'])) {
		               $user_item->setOrganisation($form_data['organisation']);
		            }
		            if (isset($form_data['position'])) {
		               $user_item->setPosition($form_data['position']);
		            }
		            if (isset($form_data['icq'])) {
		               $user_item->setICQ($form_data['icq']);
		            }
		            if (isset($form_data['skype'])) {
		               $user_item->setSkype($form_data['skype']);
		            }
		            if (isset($form_data['yahoo'])) {
		               $user_item->setYahoo($form_data['yahoo']);
		            }
		            if (isset($form_data['msn'])) {
		               $user_item->setMSN($form_data['msn']);
		            }
		            if (isset($form_data['jabber'])) {
		               $user_item->setJabber($form_data['jabber']);
		            }
		            if (isset($form_data['email'])) {
		               $user_item->setEmail($form_data['email']);
		            }
		            if (isset($form_data['street'])) {
		               $user_item->setStreet($form_data['street']);
		            }
		            if (isset($form_data['zipcode'])) {
		               $user_item->setZipcode($form_data['zipcode']);
		            }
		            if (isset($form_data['city'])) {
		               $user_item->setCity($form_data['city']);
		            }
		            if (isset($form_data['room'])) {
		               $user_item->setRoom($form_data['room']);
		            }


					if($user_item->getPicture() && isset($form_data['delete_picture'])) {
						$disc_manager = $this->_environment->getDiscManager();

						if($disc_manager->existsFile($user_item->getPicture())) $disc_manager->unlinkFile($user_item->getPicture());
						$user_item->setPicture('');
					}

		            if (!empty($form_data['language'])) {
		               $user_item->setLanguage($form_data['language']);
		            }

		            if (isset($form_data['want_mail_get_account']))
		            {
		               $user_item->setAccountWantMail($form_data['want_mail_get_account']);
		            }

					if(isset($form_data['mail_delete_entry'])) {
						$user_item->setDeleteEntryWantMail($form_data['mail_delete_entry']);
					} else {
						$user_item->setDeleteEntryWantMail('no');
					}


		            if (isset($form_data['want_mail_publish_material'])) {
		               $user_item->setPublishMaterialWantMail($form_data['want_mail_publish_material']);
		            }
		            if (isset($form_data['want_mail_open_room'])) {
		               $user_item->setOpenRoomWantMail($form_data['want_mail_open_room']);
	            	}
	                $user_item->setModificationDate(getCurrentDateTimeInMySQL());

	                // Save item
	                $user_item->save();

	                // save session
	                $this->_environment->getSessionManager()->save($session);

	                // Add modifier to all users who ever edited this item
	                $manager = $environment->getLinkModifierItemManager();
	                $manager->markEdited($user_item->getItemID());
	                
	                // set return
	                $this->_popup_controller->setSuccessfullItemIDReturn($user_item->getItemID());
	            }
            }
        }
    }


    public function isOption( $option, $string ) {
        return (strcmp( $option, $string ) == 0) || (strcmp( htmlentities($option, ENT_NOQUOTES, 'UTF-8'), $string ) == 0 || (strcmp( $option, htmlentities($string, ENT_NOQUOTES, 'UTF-8') )) == 0 );
    }

    private function assignTemplateVars() {
        $current_user = $this->_environment->getCurrentUserItem();
        $current_context = $this->_environment->getCurrentContextItem();

        // general information
        $general_information = array();

        // max upload size
        $val = $current_context->getMaxUploadSizeInBytes();
        $meg_val = round($val / 1048576);
        $general_information['max_upload_size'] = $meg_val;

        $this->_popup_controller->assign('popup', 'general', $general_information);

        // user information
        $user_information = array();
        $user_information['fullname'] = $current_user->getFullName();
        $this->_popup_controller->assign('popup', 'user', $user_information);


     }


    public function getFieldInformation($sub = '') {
		$return = array(
			'basic'	=> array(
				array(	'name'		=> 'upload_picture',
						'type'		=> 'picture',
						'mandatory' => false),
				array(	'name'		=> 'title',
						'type'		=> 'text',
						'mandatory' => false),
				array(	'name'		=> 'description',
						'type'		=> 'textarea',
						'mandatory'	=> false),
				array(	'name'		=> 'commsy_visible',
						'type'		=> 'radio',
						'mandatory'	=> false),
				array(	'name'		=> 'want_mail_get_account',
						'type'		=> 'radio',
						'mandatory'	=> false),
				array(	'name'		=> 'want_mail_open_room',
						'type'		=> 'radio',
						'mandatory'	=> false),
				array(	'name'		=> 'want_mail_publish_material',
						'type'		=> 'radio',
						'mandatory'	=> false),
				array(	'name'		=> 'language',
						'type'		=> 'select',
						'mandatory'	=> true),
				array(	'name'		=> 'birthday',
						'type'		=> 'text',
						'mandatory'	=> false),

				array(	'name'		=> 'email_visibility',
						'type'		=> 'checkbox',
						'mandatory'	=> false),
				array(	'name'		=> 'email',
						'type'		=> 'text',
						'mandatory'	=> true),
				array(	'name'		=> 'telephone',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'cellularphone',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'street',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'zipcode',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'city',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'room',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'organisation',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'position',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'homepage',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'icq',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'yahoo',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'msn',
						'type'		=> 'text',
						'mandatory'	=> false),
				array(	'name'		=> 'skype',
						'type'		=> 'text',
						'mandatory'	=> false)
			),
			'file_upload'	=> array()
		);

		return $return[$sub];
    }


	public function cleanup_session($current_iid) {
		$environment = $this->_environment;
		$session = $this->_environment->getSessionItem();

		$session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
		$session->unsetValue($environment->getCurrentModule().'_add_tags');
		$session->unsetValue($environment->getCurrentModule().'_add_files');
		$session->unsetValue($current_iid.'_post_vars');
	}
}