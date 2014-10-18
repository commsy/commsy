<?php
interface cs_export_import_interface
{
   public function export_item($id);
   public function export_sub_items($xml, $top_item);
   public function import_item($xml, $top_item, &$options);
   public function import_sub_items($xml, $top_item, &$options);
}