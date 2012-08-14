<?php
require_once('classes/controller/cs_ajax_controller.php');

class cs_ajax_ckeditor_image_browse_controller extends cs_ajax_controller {
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
	
	public function actionGetHTML() {
	   $environment = $this->_environment;
	   $translator = $environment->getTranslationObject();
	   $files = array();
      if(isset($_GET['iid']) and $_GET['iid'] != 'NEW'){
      	$item_manager = $environment->getItemManager();
      	$item = $item_manager->getItem($_GET['iid']);
      	$file_list_files = $item->getFileList();
      	if ( !$file_list_files->isEmpty() ) {
      	   $file = $file_list_files->getFirst();
      	   while( $file ) {
      	   	if(mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
      	         or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
      	         or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
      	         or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')){
      	         $files[$file->getFileID()] = $file->getFilename();
      	      }
      	      $file = $file_list_files->getNext();
      	   }
      	}
      }
      $temp_files_upload = array();
      $file_manager = $environment->getFileManager();
      $file_list_files_upload = $file_manager->getTempItemListBySessionID($environment->getSessionID());
      $file_item = $file_list_files_upload->getFirst();
      while($file_item){
         $files[$file_item->getFileID()] = $file_item->getFilename();
         $file_item = $file_list_files_upload->getNext();
      }
      unset($file_manager);
      $html  = '';
      $html .= '<script type="text/javascript" src="javascript/jQuery/jquery-1.4.1.min.js"></script>'.LF;
      $html .= '<script type="text/javascript" src="javascript/jQuery/commsy/commsy_ckeditor.js"></script>'.LF;
      $html .= '<div style="text-align:center; widht:100%;">';
      $html .= '<br/><br/>'.LF;
      $html .= '<form id="ckeditor_file_form">'.LF;
      $html .= '<input type="hidden" id="ckeditor_file_func_number" value="'.$_GET['CKEditorFuncNum'].'" />'.LF;
      $html .= '<select id="ckeditor_file_select">'.LF;
      foreach($files as $id => $filename){
         $link = 'commsy.php/'.$filename.'?cid='.$environment->getCurrentContextID().'&mod=material&fct=getfile&iid='.$id;
         $html .= '<option name="ckeditor_file" value="'.$link.'">'.$filename.'</option>'.LF;
      }
      $html .= '</select>'.LF;
      $html .= '<br/><br/>'.LF;
      $html .= '<input type="submit" value="'.$translator->getMessage('COMMON_CHOOSE_BUTTON').'">'.LF;
      $html .= '</form>'.LF;
      $html .= '</div>';
		echo $html;
	}
}