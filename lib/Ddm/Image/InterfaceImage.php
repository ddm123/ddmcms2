<?php

namespace Ddm\Image;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
interface InterfaceImage
{

    /**
     * 指定一个被修改的图片
     *
     * @param string $imageFile
     * @return $this
     */
    public function setImageFile(string $imageFile): static;

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
    public function setimgsize(string $newImage, int $w = 80, int $h = 80, string|bool $bgcolor = true, string $mask = '', int|string $padding = 0): static;

    /**
     * 生成缩略图, 这个和 setimgsize() 方法不同, 这个会裁剪图片(取图片中间部分)
     *
     * @param string $newImage 另存为这个文件
     * @param int $width
     * @param int $height
     * @param string $bgcolor
     * @return $this
     */
    public function resizeImage(string $newImage, int $width = 80, int $height = 80, string $bgcolor = 'FFFFFF'): static;

    /**
     * 给图片加水印
     *
     * @param string $watermark 可以是一个字符串(水印文字)或图片路径(图片水印)
     * @param string $font 水印文字字体文件, 如果是使用文字作为水印, 该参数是必须的
     * @param int $fontSize
     * @param string $color
     * @return $this
     */
    public function watermark(string $watermark = 'DDM', string $font = '', int $fontSize = 14, string $color = 'FF0000'): static;
}
