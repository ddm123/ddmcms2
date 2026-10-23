<?php

namespace Ddm\Image;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Gd implements InterfaceImage
{

    protected string $_imageFile = '';

    /**
     * @param string $imageFile
     * @return $this
     */
    public function setImageFile(string $imageFile): static
    {
        $this->_imageFile = $imageFile;
        return $this;
    }

    /**
     * 等比例缩放图片
     *
     * @param string $newImage 另存为这个文件
     * @param int $w
     * @param int $h
     * @param string $bgcolor 背景填充颜色(十六进制颜色字符串值), 如果为true, 则为白色(#FFFFFF), 也可以是一个文件
     * @param string $mask 附加这张图片在上面
     * @param int|string $padding 相当于CSS的padding, 只有empty($bgcolor)为false时才有效
     * @return $this
     */
    public function setimgsize(string $newImage, int $w = 80, int $h = 80, string|bool $bgcolor = true, string $mask = '', int|string $padding = 0): static
    {
        if (!$this->_imageFile || !$imagesize = getimagesize($this->_imageFile)) {
            return $this;
        }
        $fileExt = $this->_getExtension($this->_imageFile);
        $_padding = ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
        // Set a maximum height and width
        list($widthOrig, $heightOrig) = $imagesize;
        if ($w && $widthOrig > $w) {
            $width = $w;
            $height = $heightOrig * $w / $widthOrig;
            if ($height > $h) {
                $width = $width * $h / $height;
                $height = $h;
            }
        } elseif ($h && $heightOrig > $h) {
            $height = $h;
            $width = $widthOrig * $h / $heightOrig;
            if ($width > $w) {
                $height = $height * $w / $width;
                $width = $w;
            }
        } else {
            $width = $widthOrig;
            $height = $heightOrig;
        }
        $image = $this->_imagecreatefrom($this->_imageFile);
        if ($bgcolor) {
            $_image = null;
            //padding
            if ($padding) {
                if (is_numeric($padding)) {
                    $_padding['top'] = $_padding['right'] = $_padding['bottom'] = $_padding['left'] = $padding;
                } elseif (is_array($padding)) {
                    $_padding = array_merge($_padding, $padding);
                } else {
                    $temp_arr = explode(' ', $padding);
                    isset($temp_arr[0]) && $_padding['top'] = intval($temp_arr[0]);
                    isset($temp_arr[1]) && $_padding['right'] = intval($temp_arr[1]);
                    $_padding['bottom'] = isset($temp_arr[2]) ? intval($temp_arr[2]) : $_padding['top'];
                    $_padding['left'] = isset($temp_arr[3]) ? intval($temp_arr[3]) : $_padding['right'];
                }
            }
            if ($bgcolor === true) {
                $bgcolor = ['r' => 255, 'g' => 255, 'b' => 255];
            } elseif (preg_match('/^[#A-Fa-f0-9]+$/', $bgcolor)) {
                $bgcolor = $this->_formatColor($bgcolor);
            } elseif (@is_file($bgcolor)) {
                $_image = $this->_imagecreatefrom($bgcolor, $w, $h);
            } else {
                $bgcolor = ['r' => 255, 'g' => 255, 'b' => 255];
            }
            if (is_null($_image)) {
                $_image = imagecreatetruecolor($w, $h);
                imagefilledrectangle($_image, 0, 0, $w, $h, imagecolorallocate($_image, $bgcolor['r'], $bgcolor['g'], $bgcolor['b']));
            }
            imagecopyresampled($_image, $image, ($w - $width) / 2 + $_padding['left'], ($h - $height) / 2 + $_padding['top'], 0, 0, $width - $_padding['left'] - $_padding['right'], $height - $_padding['top'] - $_padding['bottom'], $widthOrig, $heightOrig);
        } else {
            $_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($_image, $image, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);
        }
        if ($mask) {
            imagecopyresampled($_image, $this->_imagecreatefrom($mask), 0, 0, 0, 0, $w, $h, $w, $h);
        }
        // Output
        switch ($fileExt) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($_image, $newImage, 75);
                break;
            case 'gif':
                imagegif($_image, $newImage);
                break;
            case 'png':
                imagepng($_image, $newImage);
                break;
            case 'bmp':
                imagewbmp($_image, $newImage);
                break;
        }
        $_image = null; // PHP8.5后弃用imagedestroy($_image);
        return $this;
    }

    /**
     * 生成缩略图, 这个和 setimgsize() 方法不同, 这个会裁剪图片(取图片中间部分)
     *
     * @param string $newImage 另存为这个文件
     * @param int $width
     * @param int $height
     * @param string $bgcolor
     * @return $this
     */
    public function resizeImage(string $newImage, int $width = 80, int $height = 80, string $bgcolor = 'FFFFFF'): static
    {
        if (!$this->_imageFile || !$imagesize = getimagesize($this->_imageFile)) {
            return $this;
        }
        $fileExt = $this->_getExtension($this->_imageFile);
        list($imgSizeWidth, $imgSizeHeight) = $imagesize;
        $imgNewWidth = $imgSizeWidth;
        $imgNewHeight = $imgSizeHeight;
        if ($imgSizeWidth < $imgSizeHeight) {
            if ($imgSizeWidth > $width) {
                $imgNewWidth = $width;
                $imgNewHeight = $imgNewWidth * $imgSizeHeight / $imgSizeWidth;
                if ($imgNewHeight < $height) {
                    $imgNewHeight = $height;
                    $imgNewWidth = $imgNewHeight * $imgSizeWidth / $imgSizeHeight;
                }
            }
        } elseif ($imgSizeHeight > $height) {
            $imgNewHeight = $height;
            $imgNewWidth = $imgNewHeight * $imgSizeWidth / $imgSizeHeight;
            if ($imgNewWidth < $width) {
                $imgNewWidth = $width;
                $imgNewHeight = $imgNewWidth * $imgSizeHeight / $imgSizeWidth;
            }
        }
        $image = imagecreatetruecolor($width, $height);
        $_x = ($width - $imgNewWidth) / 2;
        $_y = ($height - $imgNewHeight) / 2;
        if ($imgNewWidth < $width || $imgNewHeight < $height) {
            $rgb = $this->_formatColor($bgcolor);
            imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']));
        }
        imagecopyresampled($image, $this->_imagecreatefrom($this->_imageFile), $_x, $_y, 0, 0, $imgNewWidth, $imgNewHeight, $imgSizeWidth, $imgSizeHeight);
        // Output
        switch ($fileExt) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $newImage, 75);
                break;
            case 'gif':
                imagegif($image, $newImage);
                break;
            case 'png':
                imagepng($image, $newImage);
                break;
            case 'bmp':
                imagewbmp($image, $newImage);
                break;
        }
        $image = null; // PHP8.5后弃用imagedestroy($image);
        return $this;
    }

    /**
     * 给图片加水印
     *
     * @param string $watermark 可以是一个字符串(水印文字)或图片路径(图片水印)
     * @param string $font 水印文字字体文件, 如果是使用文字作为水印, 该参数是必须的
     * @param int $fontSize
     * @param string $color
     * @return $this
     */
    public function watermark(string $watermark = 'DDM', string $font = '', int $fontSize = 14, string $color = 'FF0000'): static
    {
        if (!$this->_imageFile || !$imagesize = getimagesize($this->_imageFile)) {
            return $this;
        }
        $fileExt = $this->_getExtension($this->_imageFile);
        if (is_file($watermark) && $wSize = getimagesize($watermark)) {
            $image = $this->_imagecreatefrom($this->_imageFile);
            imagecopyresampled($image, $this->_imagecreatefrom($watermark), ($imagesize[0] - $wSize[0]) / 2, ($imagesize[1] - $wSize[1]) / 2, 0, 0, $wSize[0], $wSize[1], $wSize[0], $wSize[1]);
        } elseif ($font != '' && is_file($font)) {
            $ret = $this->_formatColor($color);
            $image = $this->_imagecreatefrom($this->_imageFile);
            // Create some colors
            $grey = imagecolorallocate($image, 200, 200, 200);
            $font_color = imagecolorallocate($image, $ret['r'], $ret['g'], $ret['b']);
            // Width Height
            $w = imagesx($image);
            $h = imagesy($image);
            // Add some shadow to the text
            imagettftext($image, $fontSize, 0, 12, $h - $fontSize, $grey, $font, $watermark);
            // Add the text
            imagettftext($image, $fontSize, 0, 10, $h - ($fontSize + 2), $font_color, $font, $watermark);
        } else {
            return $this;
        }
        // Using imagepng() results in clearer text compared with imagejpeg()
        switch ($fileExt) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $this->_imageFile, 80);
                break;
            case 'gif':
                imagegif($image, $this->_imageFile);
                break;
            case 'png':
                imagepng($image, $this->_imageFile);
                break;
            case 'bmp':
                imagewbmp($image, $this->_imageFile);
                break;
        }
        $image = null; // PHP8.5后弃用imagedestroy($image);
        return $this;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function _getExtension(string $fileName): string
    {
        if (!$fileName || false === $i = strrpos($fileName, '.')) {
            return false;
        }
        return strtolower(substr($fileName, $i + 1));
    }

    private function _formatColor(string $color): array
    {
        $color = empty($color) ? '000000' : str_pad(substr(preg_replace("/[^a-fA-F0-9]/", '', $color), 0, 6), 6, '0');
        $ret = ['r' => hexdec(substr($color, 0, 2)), 'g' => hexdec(substr($color, 2, 2)), 'b' => hexdec(substr($color, 4, 2))];
        return $ret;
    }

    private function _imagecreatefrom(string $img, ?int $width = null, ?int $height = null): \GdImage|false
    {
        if ($width && $height) {
            $image = imagecreatetruecolor($width, $height);
            list($widthOrig, $heightOrig) = getimagesize($img);
            imagecopyresampled($image, $this->_imagecreatefrom($img), 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);
        } else {
            $image_ext = strtoupper(substr($img, strrpos($img, '.') + 1));
            switch ($image_ext) {
                case 'JPG':
                case 'JPEG':
                    $image = imagecreatefromjpeg($img);
                    break;
                case 'GIF':
                    $image = imagecreatefromgif($img);
                    break;
                case 'PNG':
                    $image = imagecreatefrompng($img);
                    break;
                case 'BMP':
                    $image = imagecreatefromwbmp($img);
                    break;
                default:
                    $image = false;
            }
        }
        return $image;
    }
}
