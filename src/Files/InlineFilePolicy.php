<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace App\Files;

use Symfony\Component\HttpFoundation\Response;

/**
 * Which uploaded files a browser may show in place, and the headers that keep
 * every served file a file.
 *
 * Uploads are not restricted by type, so a file can be HTML or SVG. Shown in
 * place under the site's own origin, such a file would run as a page of it.
 */
final class InlineFilePolicy
{
    /** Raster images: shown in place, as room logos and backgrounds too. */
    private const array IMAGES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/bmp',
        'image/x-ms-bmp',
    ];

    private const string PDF = 'application/pdf';

    public static function isImage(string $mimeType): bool
    {
        return in_array(self::bare($mimeType), self::IMAGES, true);
    }

    /**
     * Raster images, PDF, audio and video; anything else is downloaded.
     */
    public static function allowsInline(string $mimeType): bool
    {
        $mimeType = self::bare($mimeType);

        return self::isImage($mimeType)
            || self::PDF === $mimeType
            || str_starts_with($mimeType, 'audio/')
            || str_starts_with($mimeType, 'video/');
    }

    /**
     * The browser takes the declared type as is and, opened on its own, runs
     * no script; PDF viewers refuse the sandbox, so PDF goes without.
     */
    public static function protect(Response $response, string $mimeType): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (self::PDF !== self::bare($mimeType)) {
            $response->headers->set('Content-Security-Policy', 'sandbox');
        }

        return $response;
    }

    private static function bare(string $mimeType): string
    {
        return strtolower(trim(explode(';', $mimeType)[0]));
    }
}
