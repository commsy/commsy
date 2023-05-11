<?php

namespace App\WOPI\Discovery\Response;

enum WOPIZone: string
{
    case INTERNAL_HTTP = 'internal-http';
    case INTERNAL_HTTPS = 'internal-https';
    case EXTERNAL_HTTP = 'external-http';
    case EXTERNAL_HTTPS = 'external-https';
}
