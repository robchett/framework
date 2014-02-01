<?php

namespace core\classes;

class image_resizer {

    private $image;
    private $width;
    private $height;
    private $type;
    private $imageResized;

    function __construct($fileName) {
        $this->image = $this->open_image($fileName);
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    private function open_image($file) {
        $extension = strtolower(strrchr($file, '.'));
        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $this->type = 'jpg';
            $img = @imagecreatefromjpeg($file);
            break;
            case '.gif':
                $this->type = 'gif';
                $img = @imagecreatefromgif($file);
                break;
            case '.png':
                $this->type = 'png';
                $img = @imagecreatefrompng($file);
                break;
            default:
                $img = false;
                break;
        }
        return $img;
    }

    public function resizeImage($width, $height, $crop = false) {
        // resize
        if ($crop) {
            if ($this->width < $width or $this->height < $height) return "Picture is too small!";
            $ratio = max($width / $this->width, $height / $this->height);
            if($width / $this->width < $height / $this->height) {
                $x = ($this->width - $width / $ratio) / 2;
                $y = 0;
            } else {
                $x = 0;
                $y = ($this->height - $height / $ratio) / 2;
            }
            $this->height = $height / $ratio;
            $this->width = $width / $ratio;
        } else {
            if ($this->width < $width and $this->height < $height) return "Picture is too small!";
            $ratio = min($width / $this->width, $height / $this->height);
            $width = $this->width * $ratio;
            $height = $this->height * $ratio;
            $x = 0;
            $y = 0;
        }

        $new = imagecreatetruecolor($width, $height);

        // preserve transparency
        if ($this->type == "gif" or $this->type == "png") {
            imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        echo 'Width:' . $width . ' Height:' . $height;
        echo 'Width:' . $this->width . ' Height:' . $this->height;
        echo 'X:' .$x . ' Y:' . $y;

        $this->imageResized = imagecreatetruecolor($width, $height);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, $x, $y, $width, $height, $this->width, $this->height);
    }

    public function saveImage($savePath, $imageQuality = "100") {

        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);
        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                }
                break;
            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                }
                break;
            case '.png':
                $scaleQuality = round(($imageQuality / 100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;

                if (imagetypes() & IMG_PNG) {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                }
                break;
            default:
                break;
        }
        imagedestroy($this->imageResized);
    }
}