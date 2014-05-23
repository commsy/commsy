<?php
interface cs_export_import_interface
{
   public function export_item($id);
   public function export_sub_items($top_item, $xml);
   public function import_item($xml);
}