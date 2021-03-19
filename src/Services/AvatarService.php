<?php

namespace App\Services;

use IDCI\Bundle\ColorSchemeBundle\Model\Color;

use App\Utils\UserService;

class AvatarService
{    
    
    private $userService;
    
    private $kernelProjectDir;
    
    private $type;

    private $colorScheme;

    private $itemId;
    
    private $user;
    
    private $imageWidth;
    
    private $imageHeight;

    public function __construct(UserService $userService, $kernelProjectDir)
    {
        $this->userService = $userService;
        
        $this->kernelProjectDir = $kernelProjectDir;
        
        $this->imageWidth = 100;
        
        $this->imageHeight = 100;
    }

    /**
     * Returns a new guest session id
     * 
     * @param  int $portalId
     * 
     * @return string session id
     */
    public function getAvatar($itemId, $type = 0, $colorScheme = 0)
    {
        $this->itemId = $itemId;
        $this->user = $this->userService->getUser($itemId);
        $this->type = $type;
        $this->colorScheme = $colorScheme;
        
        return $this->generateAvatar();
    }
    
    function generateAvatar() {
        if ($this->type == 0 || $this->type == 1) {
            return $this->generateInitialsAvatar();
        }
        
        return file_get_contents($this->kernelProjectDir . '/assets/uikit2/img/user_unknown.gif');
    }
    
    function generateInitialsAvatar() {
        if ($this->type == 1) {
            $image = @imagecreatefromgif($this->kernelProjectDir . '/assets/uikit2/img/user_unknown.gif');
            $imageSize = getimagesize($this->kernelProjectDir . '/assets/uikit2/img/user_unknown.gif');
            $this->imageWidth = $imageSize[0];
            $this->imageHeight = $imageSize[1];
            $fontSize = 50;
        } else {
            $image = @ImageCreate($this->imageWidth, $this->imageHeight);
            $fontSize = 38;
        }

        $colors = $this->getColors($image);

        imagefill($image, 0, 0, $colors['background']);
        
        $initialString = strtoupper(mb_substr($this->user->getFirstname(), 0, 1)).strtoupper(mb_substr($this->user->getLastname(), 0, 1));
        if (!$initialString) {
            $initialString = strtoupper(substr($this->user->getUserId(), 0, 1));
        }

        $font = $this->kernelProjectDir . '/src/Resources/fonts/LiberationSans-Regular.ttf';
        $angle = 0;
        
        $textBox = imagettfbbox($fontSize,$angle,$font,$initialString);

        // Get your Text Width and Height
        $textWidth = $textBox[2]-$textBox[0];
        $textHeight = $textBox[7]-$textBox[1];
        
        // Calculate coordinates of the text
        $x = ($this->imageWidth/2) - ($textWidth/2);
        $y = ($this->imageHeight/2) - ($textHeight/2);
        
        imagettftext($image, $fontSize, $angle, $x, $y, $colors['text'], $font, $initialString);
        
        ob_start();
        imagepng($image);
        $stringdata = ob_get_contents();
        ob_end_clean();
        return $stringdata;
    }
    
    function getColors($image) {
        $colors = [];
        
        $colorBaseString = $this->user->getUserId();
            
        $hexValue = dechex(crc32($colorBaseString.strrev($colorBaseString)));
        $colorCode = substr($hexValue, 0, 6);
            
        $color = new Color('#'.$colorCode);
        
        if ($this->colorScheme == 0) {
            $hsl = $color->toHSL();
            $l = $hsl->getLightness() + 50;
            $decColor = $hsl->setLightness($l > 90 ? 90 : $l)->toDec();
            $colors['background'] = ImageColorAllocate ($image, $decColor->getRed(), $decColor->getGreen(), $decColor->getBlue());
            $colors['text'] = ImageColorAllocate ($image, 120, 120, 120);
        } else if ($this->colorScheme == 1) {
            $hsl = $color->toHSL();
            $l = $hsl->getLightness();
            $decColor = $hsl->setLightness($l > 90 ? 90 : $l)->toDec();
            if ($this->type == 1) {
                $colors['background'] = ImageColorAllocate ($image, 255, 255, 255);
            } else {
                $colors['background'] = ImageColorAllocate ($image, 220, 220, 220);
            }
            $colors['text'] = ImageColorAllocate ($image, $decColor->getRed(), $decColor->getGreen(), $decColor->getBlue());
        } else if ($this->colorScheme == 2) {
            $color = new Color('#6593B3');
            
            if (strlen($colorBaseString) % 2 == 1) {
                $percent = $percent = strlen($colorBaseString) * -1.5;
                $colors['text'] = ImageColorAllocate ($image, 240, 240, 240);
            } else {
                $percent = $percent = strlen($colorBaseString) * 1.5;
                $colors['text'] = ImageColorAllocate ($image, 120, 120, 120);
            }
            
            $hsl = $color->toHSL();
            $l = $hsl->getLightness() + $percent;
            $decColor = $hsl->setLightness($l > 90 ? 90 : $l)->toDec();
            $colors['background'] = ImageColorAllocate ($image, $decColor->getRed(), $decColor->getGreen(), $decColor->getBlue());
        } else if ($this->colorScheme == 3) {
            $color = new Color('#6593B3');
            
            if (strlen($colorBaseString) % 2 == 1) {
                $percent = $percent = strlen($colorBaseString) * -1;
            } else {
                $percent = $percent = strlen($colorBaseString) * 1;
            }
            
            $hsl = $color->toHSL();
            $l = $hsl->getLightness() + $percent;
            $decColor = $hsl->setLightness($l > 100 ? 100 : $l)->toDec();
            $colors['text'] = ImageColorAllocate ($image, $decColor->getRed(), $decColor->getGreen(), $decColor->getBlue());
            $colors['background'] = ImageColorAllocate ($image, 240, 240, 240);
        }
        
        return $colors;
    }

    function getUnknownUserImage() {
        return file_get_contents($this->kernelProjectDir . '/assets/uikit2/img/user_unknown.gif');
    }
}