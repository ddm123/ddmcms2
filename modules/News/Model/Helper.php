<?php

namespace News\Model;

use Core\Model\Url;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Helper
{

    /**
     * @param int $categoryId
     * @param string $urlKey
     * @return string
     */
    public function getCategoryUrlFromUrlKey(int $categoryId, string $urlKey): string
    {
        return Url::singleton()->getUrl($urlKey, 'news/category/view', ['id' => $categoryId]);
    }

    /**
     * @param int $newsId
     * @param string $urlKey
     * @return string
     */
    public function getNewsUrlFromUrlKey(int $newsId, string $urlKey): string
    {
        return Url::singleton()->getUrl($urlKey, 'news/view', ['id' => $newsId]);
    }
}
