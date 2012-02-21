<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_edit_controller extends cs_room_controller {
		protected $_item_id = null;
		/*
		protected $_browse_ids = array();
		protected $_position = -1;
		protected $_manager = null;
		*/

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// init variables
			/*
			$this->getViewMode() = 'browse';
			$this->_filter = array();
			$this->_paging = array(
				'offset'	=> 0,
				'limit'		=> 20
			);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
		}
		
		protected function checkFormData() {
			try {
				$this->checkForm();
				
				return false;
			} catch(cs_form_mandatory_exception $e) {
				// TODO: implement in edit form
				echo "mandatory catched";
			} catch(cs_form_value_exception $e) {
				// TODO: implement in edit form
				echo "value catched";
			}
		}
		
		private function checkForm() {
			foreach($this->getFieldInformation() as $field) {
				
				// check mandatory
				if(isset($field['mandatory']) || $field['mandatory'] === true) {
					if(!isset($_POST['form_data'][$field['name']]) || trim($_POST['form_data'][$field['name']]) === '') {
						throw new cs_form_mandatory_exception('missing mandatory field');
						
						return false;
					}
				}
				
				// check values
				// TODO:
				//throw new cs_form_value_exception('value exception');
			}
		}
		
		abstract protected function getFieldInformation();
		
		protected function setFilesForItem(cs_item $item, $post_file_ids) {
			$session = $this->_environment->getSessionItem();
			
			// temp files
			$temp_files_array = array();
			$file_manager = $this->_environment->getFileManager();
			$file_manager->resetLimits();
			$file_manager->setTempUploadSessionIdLimit($this->_environment->getSessionId());
			$file_manager->select();
			$file_list = $file_manager->get();
			$file_item = $file_list->getFirst();
			while($file_item) {
				$temp_files_array[] = $file_item->getFileID();
				$file_manager->resetTempUpload($file_item);
				
				$file_item = $file_list->getNext();
			}
			unset($file_manager);
			
			// files
			if(isset($post_file_ids) && !empty($post_file_ids)) {
				$file_ids = $post_file_ids;
			} else {
				$file_ids = isset($_POST['filelist']) ? $_POST['filelist'] : array();
			}
			
			$files = $session->getValue($this->_environment->getCurrentModule() . '_add_files');
			
			$file_id_array = array();
			

if ( !empty($files)
     and count($files) >= count($file_ids)
   ) {
   $file_man = $this->_environment->getFileManager();
   foreach ( $files as $file_data ) {
      if ( in_array(trim($file_data["file_id"]), $file_ids) ) {
         if ( isset($file_data["tmp_name"]) and file_exists($file_data["tmp_name"]) ) { // create file entries for uploaded files
            $file_item = $file_man->getNewItem();
            $file_item->setTempKey($file_data["file_id"]);
            // trim space
            $file_data['name'] = trim($file_data['name']);
            $file_item->setPostFile($file_data);
            $file_item->save();
            unlink($file_data["tmp_name"]);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
            $file_id_array[] = $file_item->getFileID();
         } else {
            $file_id_array[] = $file_data["file_id"];
         }
      }
   }
   #$item->setFileIDArray($file_id_array);
   $temp_merge_array = array_merge($file_id_array, $temp_files_array);
   $item->setFileIDArray($temp_merge_array);
} elseif ( !empty($file_ids) ) {
   $temp_array = array();
   foreach ($file_ids as $file_id) {
      if ( is_numeric($file_id) ) {
         $temp_array[] = $file_id;
      } else {
         if ( !isset($file_manager) ) {
            $file_manager = $this->_environment->getFileManager();
            $file_manager->setContextLimit($this->_environment->getCurrentContextID());
         }
         $temp_key = $file_manager->getFileIDForTempKey($file_id);
         if ( !empty($temp_key) and is_numeric($temp_key) ) {
            $temp_array[] = $temp_key;
         } elseif ( !empty($files) ) {
            foreach ( $files as $file_data ) {
               if ( $file_data["file_id"] == $file_id ) {
                  if ( isset($file_data["tmp_name"]) and file_exists($file_data["tmp_name"]) ) { // create file entries for uploaded files
                     $file_item = $file_manager->getNewItem();
                     $file_item->setTempKey($file_data["file_id"]);
                     $file_item->setPostFile($file_data);
                     $file_item->save();
                     unlink($file_data["tmp_name"]);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
                     $temp_array[] = $file_item->getFileID();
                  }
               }
            }
         }
      }
   }
   unset($file_manager);
   #$item->setFileIDArray($temp_array);
   $temp_merge_array = array_merge($temp_array, $temp_files_array);
   $item->setFileIDArray($temp_merge_array);
} else {
   #$item->setFileIDArray(array());
   $item->setFileIDArray($temp_files_array);
}
		}
	}