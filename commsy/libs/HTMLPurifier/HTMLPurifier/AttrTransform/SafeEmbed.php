<?php

class HTMLPurifier_AttrTransform_SafeEmbed extends HTMLPurifier_AttrTransform
{
    public $name = "SafeEmbed";

    public function transform($attr, $config, $context) {
        $attr['allowscriptaccess'] = 'never';
        $attr['allownetworking'] = 'internal';
        if (!isset($attr['type'])) $attr['type'] = 'application/x-shockwave-flash';
//         $attr['type'] = 'application/x-shockwave-flash';
        return $attr;
    }
}

// vim: et sw=4 sts=4
