<?php

namespace CommsyBundle\Utils;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\LessFilter;

class LessNonCachedFilter extends LessFilter
{
    public function filterLoad(AssetInterface $asset)
    {
        $root = $asset->getSourceRoot();
        $path = $asset->getSourcePath();

        $filename = realpath($root . '/' . $path);

        if (file_exists($filename)) {
            touch($filename);
        }

        parent::filterLoad($asset);
    }
}