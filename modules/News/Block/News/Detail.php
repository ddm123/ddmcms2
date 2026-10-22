<?php

namespace News\Block\News;

use Core\Block\AbstractModel;
use Ddm;
use Ddm\Db;
use News\Model\Category;
use News\Model\News;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \News\Block\View getParentBlock()
 */
class Detail extends AbstractModel
{

    protected mixed $_prvNext = null;

    public function init(): static
    {
        $this->setTemplate('detail.phtml');
        return parent::init();
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->getParentBlock()->getCategory();
    }

    /**
     * @return News
     */
    public function getNews(): News
    {
        return $this->getParentBlock()->getNews();
    }

    /**
     * @return array
     */
    public function getPrveNext(): array
    {
        if ($this->_prvNext === null) {
            $newsId = (int) $this->getNews()->getId();
            $news = new News();
            $news->setLanguageId(Ddm::getLanguage()->language_id);
            $news->addCategoryToSelect();
            $news->addCategoryUrlToSelect();
            $news->addAttributeToSelect('title');
            $news->addAttributeToSelect('url_key');
            $prvSelect = clone $news->getSelect();
            $prvSelect->orderBy("main_table.news_id", "DESC")->where("main_table.news_id", '<', $newsId)->limit(1);
            $prvSqlParams = [];
            $prvSql = $prvSelect->toSql($prvSqlParams);
            $nextSelect = clone $news->getSelect();
            $nextSelect->orderBy("main_table.news_id", "ASC")->where("main_table.news_id", '>', $newsId)->limit(1);
            $nextParams = [];
            $nextSql = $nextSelect->toSql($nextParams);
            $this->_prvNext = Db::getReadConn()->query("({$prvSql}) UNION ALL ({$nextSql})", array_merge($prvSqlParams, $nextParams))->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $this->_prvNext;
    }

    protected function _beforeToHtml(): static
    {
        $cacheLifetime = Ddm::getConfig()->getConfigValue('news/cache/view');
        $cacheLifetime = $cacheLifetime === null ? 1800 : $cacheLifetime * 60;
        //小于5分钟的缓存没任何意义, 所以这里只有设置大于5分钟以上才启用缓存
        if ($cacheLifetime === 0 || $cacheLifetime >= 300) {
            $this->setCache('news_view_' . $this->getNews()->getId(), ['news_view'], $cacheLifetime);
        }
        return parent::_beforeToHtml();
    }
}
