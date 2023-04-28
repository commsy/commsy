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

namespace App\Services;

use App\Utils\UserService;
use cs_user_item;
use OzdemirBurak\Iris\Color\Hex;
use OzdemirBurak\Iris\Exceptions\InvalidColorException;

class AvatarService
{
    private int $type;

    private int $colorScheme;

    private ?cs_user_item $user = null;

    private int $imageWidth = 100;

    private int $imageHeight = 100;

    /**
     * @param string $kernelProjectDir
     */
    public function __construct(private readonly UserService $userService, private $kernelProjectDir)
    {
    }

    /**
     * Returns a new guest session id.
     *
     * @return string session id
     *
     * @throws InvalidColorException
     */
    public function getAvatar($itemId, int $type = 0, int $colorScheme = 0)
    {
        $this->user = $this->userService->getUser($itemId);
        $this->type = $type;
        $this->colorScheme = $colorScheme;

        return $this->generateAvatar();
    }

    /**
     * @throws InvalidColorException
     */
    public function generateAvatar(): false|string
    {
        if (0 == $this->type || 1 == $this->type) {
            return $this->generateInitialsAvatar();
        }

        return file_get_contents($this->kernelProjectDir.'/src/Resources/img/user_unknown.gif');
    }

    /**
     * @throws InvalidColorException
     */
    public function generateInitialsAvatar(): false|string
    {
        if (1 == $this->type) {
            $image = @imagecreatefromgif($this->kernelProjectDir.'/src/Resources/img/user_unknown.gif');
            $imageSize = getimagesize($this->kernelProjectDir.'/src/Resources/img/user_unknown.gif');
            $this->imageWidth = $imageSize[0];
            $this->imageHeight = $imageSize[1];
            $fontSize = 50;
        } else {
            $image = @imagecreate($this->imageWidth, $this->imageHeight);
            $fontSize = 38;
        }

        $colors = $this->getColors($image);

        imagefill($image, 0, 0, $colors['background']);

        $initialString = strtoupper(mb_substr($this->user->getFirstname(), 0,
            1)).strtoupper(mb_substr($this->user->getLastname(), 0, 1));
        if (!$initialString) {
            $initialString = strtoupper(substr($this->user->getUserId(), 0, 1));
        }

        $font = $this->kernelProjectDir.'/src/Resources/fonts/LiberationSans-Regular.ttf';
        $angle = 0;

        $textBox = imagettfbbox($fontSize, $angle, $font, $initialString);

        // Get your Text Width and Height
        $textWidth = $textBox[2] - $textBox[0];
        $textHeight = $textBox[7] - $textBox[1];

        // Calculate coordinates of the text
        $x = ($this->imageWidth / 2) - ($textWidth / 2);
        $y = ($this->imageHeight / 2) - ($textHeight / 2);

        imagettftext($image, $fontSize, $angle, $x, $y, $colors['text'], $font, $initialString);

        ob_start();
        imagepng($image);
        $stringdata = ob_get_contents();
        ob_end_clean();

        return $stringdata;
    }

    /**
     * @throws InvalidColorException
     */
    public function getColors($image): array
    {
        $colors = [];

        $colorBaseString = $this->user->getUserId();

        $hexValue = dechex(crc32($colorBaseString.strrev($colorBaseString)));
        $colorCode = substr($hexValue, 0, 6);
        $hexColor = new Hex('#'.$colorCode);

        if (0 == $this->colorScheme) {
            $hslColor = $hexColor->toHsl();
            $l = $hslColor->lightness() + 50;
            $hslColor->lightness($l > 90 ? 90 : $l);
            $rgbColor = $hslColor->toRgb();
            $colors['background'] = imagecolorallocate($image, $rgbColor->red(), $rgbColor->green(), $rgbColor->blue());
            $colors['text'] = imagecolorallocate($image, 120, 120, 120);
        } else {
            if (1 == $this->colorScheme) {
                $hslColor = $hexColor->toHsl();
                $l = $hslColor->lightness();
                $hslColor->lightness($l > 90 ? 90 : $l);
                $rgbColor = $hslColor->toRgb();
                if (1 == $this->type) {
                    $colors['background'] = imagecolorallocate($image, 255, 255, 255);
                } else {
                    $colors['background'] = imagecolorallocate($image, 220, 220, 220);
                }
                $colors['text'] = imagecolorallocate($image, $rgbColor->red(), $rgbColor->green(), $rgbColor->blue());
            } else {
                if (2 == $this->colorScheme) {
                    $hexColor = new Hex('#6593B3');

                    if (1 == strlen($colorBaseString) % 2) {
                        $percent = strlen($colorBaseString) * -1.5;
                        $colors['text'] = imagecolorallocate($image, 240, 240, 240);
                    } else {
                        $percent = strlen($colorBaseString) * 1.5;
                        $colors['text'] = imagecolorallocate($image, 120, 120, 120);
                    }

                    $hslColor = $hexColor->toHsl();
                    $l = $hslColor->lightness() + $percent;
                    $hslColor->lightness($l > 90 ? 90 : $l);
                    $rgbColor = $hslColor->toRgb();

                    $colors['background'] = imagecolorallocate($image, $rgbColor->red(), $rgbColor->green(),
                        $rgbColor->blue());
                } else {
                    if (3 == $this->colorScheme) {
                        $hexColor = new Hex('#6593B3');

                        if (1 == strlen($colorBaseString) % 2) {
                            $percent = strlen($colorBaseString) * -1;
                        } else {
                            $percent = strlen($colorBaseString) * 1;
                        }

                        $hslColor = $hexColor->toHsl();
                        $l = $hslColor->lightness() + $percent;
                        $hslColor->lightness($l > 100 ? 100 : $l);
                        $rgbColor = $hslColor->toRgb();

                        $colors['text'] = imagecolorallocate($image, $rgbColor->red(), $rgbColor->green(),
                            $rgbColor->blue());
                        $colors['background'] = imagecolorallocate($image, 240, 240, 240);
                    }
                }
            }
        }

        return $colors;
    }

    public function getUnknownUserImage(): false|string
    {
        return file_get_contents($this->kernelProjectDir.'/src/Resources/img/user_unknown.gif');
    }
}
