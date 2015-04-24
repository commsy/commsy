<?php
require_once('classes/controller/cs_ajax_controller.php');

class cs_ajax_ckeditor_image_upload_controller extends cs_ajax_controller {
   /**
	 * constructor
	 */
	public function __construct(cs_environment $environment) {
		// call parent
		parent::__construct($environment);
	}
	
	public function process() {
		// call parent
		parent::process();
	}
	
	public function actionSaveFile() {
	   $environment = $this->_environment;
	   $session = $environment->getSessionItem();
	   
	   include_once('functions/development_functions.php');

      if(!empty($_FILES)) {
         $post_file_ids = array();
         $tempFile = $_FILES['upload']['tmp_name'];
         
         $focus_element_onload = 'Filedata';
         
         $file_array = array();

         if(   !empty($tempFile) &&
               $_FILES['upload']['size'] > 0) {
            if(   isset($_REQUEST['c_virus_scan']) &&
                  $_REQUEST['c_virus_scan'] &&
                  isset($_REQUEST['c_virus_scan_cron']) &&
                  !empty($_REQUEST['c_virus_scan_cron']) &&
                  !$_REQUEST['c_virus_scan_crom']) {
               // use virus scanner
               require_once('classes/cs_virus_scan.php');
               $virus_scanner = new cs_virus_scan($environment);
               if ($virus_scanner->isClean($tempFile,$tempFile)) {
                  move_uploaded_file($tempFile, $tempFile . 'commsy3');
                  $temp_array = array();
                  $temp_array['name'] = $_FILES['upload']['name'];
                  $temp_array['tmp_name'] = $tempFile. 'commsy3';
                  $temp_array['file_id'] = $temp_array['name'].'_' . getCurrentDateTimeInMySQL();
                  $file_array[] = $temp_array;
               } else {
                  $params = array();
                  $params['environment'] = $environment;
                  $params['with_modifying_actions'] = true;
                  $params['width'] = 500;
                  $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
                  unset($params);
                  $errorbox->setText($virus_scanner->getOutput());
                  $page->add($errorbox);
                  $focus_element_onload = '';
                  $error_on_upload = true;
               }
            } else {
               require_once('functions/date_functions.php');
               move_uploaded_file($tempFile, $tempFile . 'commsy3');
               $temp_array = array();
               $temp_array['name'] = $_FILES['upload']['name'];
               $temp_array['tmp_name'] = $tempFile . 'commsy3';
               $temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
               $file_array[] = $temp_array;
            }
         }
         
         $file_data = $file_array[0];
         $file_manager = $environment->getFileManager();
         $file_item = $file_manager->getNewItem();
         $file_item->setTempKey($file_data["file_id"]);
         $file_item->setPostFile($file_data);
         $file_item->setTempUploadFromEditorSessionID($environment->getSessionID());
         $file_item->save();
         unlink($file_data["tmp_name"]);
         
         // Nach dem Speichern des Eintrags die Items-Tabelle anhand temp=true und der extras->SESSION_ID durchsuchen.
         // Text im Textfeld nach Dateinamen parsen und passende Dateien aus der files-Tabelle mit dem Item verlinken.
         // Extras temp und id zurücksetzen.
         // cron für das regelmäßige löschen von temp-files.
      	$callback_function  = '';
      	$callback_function .= '<script type="text/javascript">'.LF;
         $callback_function .= '<!--'.LF;
      	$callback_function .= 'var fileTypeFunction = function () {';
         $callback_function .= 'var dialog = this.getDialog();';
         $callback_function .= 'if(dialog.getName() == "CommSyVideo"){';
         $callback_function .= 'var element = dialog.getContentElement( "videoTab", "videoType" );';
         $callback_function .= 'element.setValue("'.$file_item->getMime().'")';
         $callback_function .= '}';
         $callback_function .= '};';
      	$callback_function .= 'window.parent.CKEDITOR.tools.callFunction('.$_GET['CKEditorFuncNum'].', "commsy.php/?cid='.$environment->getCurrentContextID().'&mod=material&fct=getfile&iid='.$file_item->getFileID().'", fileTypeFunction);'.LF;
      	$callback_function .= '-->'.LF;
      	$callback_function .= '</script>'.LF;
      	echo $callback_function;
      }
      $environment->getSessionManager()->save($session);
      exit;
	}
}