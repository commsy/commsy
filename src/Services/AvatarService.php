<?php

namespace App\Services;

use App\Utils\UserService;
use cs_user_item;
use OzdemirBurak\Iris\Color\Hex;
use OzdemirBurak\Iris\Exceptions\InvalidColorException;

class AvatarService
{
    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @var string
     */
    private string $kernelProjectDir;

    /**
     * @var int
     */
    private int $type;

    /**
     * @var int
     */
    private int $colorScheme;

    /**
     * @var cs_user_item|null
     */
    private ?cs_user_item $user;

    /**
     * @var int
     */
    private int $imageWidth = 100;

    /**
     * @var int
     */
    private int $imageHeight = 100;

    public function __construct(UserService $userService, $kernelProjectDir)
    {
        $this->userService = $userService;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * Returns a new guest session id
     *
     * @param $itemId
     * @param int $type
     * @param int $colorScheme
     * @return string session id
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
     * @return false|string
     * @throws InvalidColorException
     */
    public function generateAvatar()
    {
        if ($this->type == 0 || $this->type == 1) {
            return $this->generateInitialsAvatar();
        }

        return file_get_contents($this->kernelProjectDir . '/src/Resources/uikit2/img/user_unknown.gif');
    }

    /**
     * @return false|string
     * @throws InvalidColorException
     */
    public function generateInitialsAvatar()
    {
        if ($this->type == 1) {
            $image = @imagecreatefromgif($this->kernelProjectDir . '/src/Resources/uikit2/img/user_unknown.gif');
            $imageSize = getimagesize($this->kernelProjectDir . '/src/Resources/uikit2/img/user_unknown.gif');
            $this->imageWidth = $imageSize[0];
            $this->imageHeight = $imageSize[1];
            $fontSize = 50;
        } else {
            $image = @ImageCreate($this->imageWidth, $this->imageHeight);
            $fontSize = 38;
        }

        $colors = $this->getColors($image);

        imagefill($image, 0, 0, $colors['background']);

        $initialString = strtoupper(mb_substr($this->user->getFirstname(), 0,
                1)) . strtoupper(mb_substr($this->user->getLastname(), 0, 1));
        if (!$initialString) {
            $initialString = strtoupper(substr($this->user->getUserId(), 0, 1));
        }

        $font = $this->kernelProjectDir . '/src/Resources/fonts/LiberationSans-Regular.ttf';
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
     * @param $image
     * @return array
     * @throws InvalidColorException
     */
    public function getColors($image): array
    {
        $colors = [];

        $colorBaseString = $this->user->getUserId();

        $hexValue = dechex(crc32($colorBaseString . strrev($colorBaseString)));
        $colorCode = substr($hexValue, 0, 6);
        $hexColor = new Hex('#' . $colorCode);

        if ($this->colorScheme == 0) {
            $hslColor = $hexColor->toHsl();
            $l = $hslColor->lightness() + 50;
            $hslColor->lightness($l > 90 ? 90 : $l);
            $rgbColor = $hslColor->toRgb();
            $colors['background'] = ImageColorAllocate($image, $rgbColor->red(), $rgbColor->green(), $rgbColor->blue());
            $colors['text'] = ImageColorAllocate($image, 120, 120, 120);
        } else {
            if ($this->colorScheme == 1) {
                $hslColor = $hexColor->toHsl();
                $l = $hslColor->lightness();
                $hslColor->lightness($l > 90 ? 90 : $l);
                $rgbColor = $hslColor->toRgb();
                if ($this->type == 1) {
                    $colors['background'] = ImageColorAllocate($image, 255, 255, 255);
                } else {
                    $colors['background'] = ImageColorAllocate($image, 220, 220, 220);
                }
                $colors['text'] = ImageColorAllocate($image, $rgbColor->red(), $rgbColor->green(), $rgbColor->blue());
            } else {
                if ($this->colorScheme == 2) {
                    $hexColor = new Hex('#6593B3');

                    if (strlen($colorBaseString) % 2 == 1) {
                        $percent = strlen($colorBaseString) * -1.5;
                        $colors['text'] = ImageColorAllocate($image, 240, 240, 240);
                    } else {
                        $percent = strlen($colorBaseString) * 1.5;
                        $colors['text'] = ImageColorAllocate($image, 120, 120, 120);
                    }

                    $hslColor = $hexColor->toHsl();
                    $l = $hslColor->lightness() + $percent;
                    $hslColor->lightness($l > 90 ? 90 : $l);
                    $rgbColor = $hslColor->toRgb();

                    $colors['background'] = ImageColorAllocate($image, $rgbColor->red(), $rgbColor->green(),
                        $rgbColor->blue());
                } else {
                    if ($this->colorScheme == 3) {
                        $hexColor = new Hex('#6593B3');

                        if (strlen($colorBaseString) % 2 == 1) {
                            $percent = strlen($colorBaseString) * -1;
                        } else {
                            $percent = strlen($colorBaseString) * 1;
                        }

                        $hslColor = $hexColor->toHsl();
                        $l = $hslColor->lightness() + $percent;
                        $hslColor->lightness($l > 100 ? 100 : $l);
                        $rgbColor = $hslColor->toRgb();

                        $colors['text'] = ImageColorAllocate($image, $rgbColor->red(), $rgbColor->green(),
                            $rgbColor->blue());
                        $colors['background'] = ImageColorAllocate($image, 240, 240, 240);
                    }
                }
            }
        }

        return $colors;
    }

    /**
     * @return false|string
     */
    public function getUnknownUserImage()
    {
        return file_get_contents($this->kernelProjectDir . '/src/Resources/uikit2/img/user_unknown.gif');
    }
}