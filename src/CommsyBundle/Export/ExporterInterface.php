<?php

namespace CommsyBundle\Export;

interface ExporterInterface
{
    public function isEnabled();

    public function isExportAllowed($item);

    public function exportItem($item);
}