<?php
	class cs_base_exception extends Exception {
		public function resetTemplateVars($tpl_engine) {
			$tpl_vars = array_keys($tpl_engine->getTemplateVars());
			
			foreach($tpl_vars as $var) {
				if($var === 'basic' || $var === 'environment') continue;
				
				$tpl_engine->clearAssign($var);
			}
		}
	}