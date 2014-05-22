<?php
interface cs_export_import_interface
{
   public function export_item($id);
   public function import_item($xml);
}