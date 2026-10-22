<?php

namespace News\Model\Resource;

use Core\Model\AbstractModel;
use Core\Model\Resource\AbstractModel as ResourceAbstractModel;
use Core\Model\Resource\Entity;
use Core\Model\Url;
use Ddm;
use Ddm\Db;
use News\Model\News as ModelNews;
use News\Model\News\Urlindex;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class News extends Entity
{

    protected function _init(): static
    {
        $this->_setTable('news', 'news_id');
        return $this;
    }

    /**
     * 批量修改一条或多条新闻的分类
     * @param int $categoryId
     * @param int|array $newsId
     * @return $this
     */
    public function changeCategory(int $categoryId, int|array $newsId): static
    {
        if (!empty($newsId)) {
            $categoryId = (int) $categoryId;
            if (is_array($newsId)) {
                if (count($newsId) < 2) {
                    $newsId = (int) reset($newsId);
                }
            } else {
                $newsId = (int) $newsId;
            }
            Db::table('news')->where('news_id', '=', $newsId)->update(['category_id' => $categoryId]);
        }
        return $this;
    }

    /**
     * 累加浏览次数
     * @param int $newsId
     * @param int $i 增加多少, 默认加1
     * @return $this
     */
    public function addView(int $newsId, int $i = 1): static
    {
        if (($i = (int) $i) && $newsId = (int) $newsId) {
            Db::table('news')->where('news_id', '=', $newsId)->increment('views', $i);
        }
        return $this;
    }

    /**
     * @param ModelNews $news
     * @param string $urlKey
     * @return $this
     */
    public function loadFromUrlKey(ModelNews $news, string $urlKey): static
    {
        $_news = new ModelNews();
        $result = $_news->setLanguageId($news->language_id)->addAttributeToSelect('url_key')->addAttributeToFilter('url_key', $urlKey)->getSelect()->first();
        if ($result) {
            $news->addData($result)->setOrigData($result, null, true);
        }
        return $this;
    }

    /**
     * @param int $newsId
     * @return $this
     */
    public function saveUrlIndex(int $newsId): static
    {
        if (Ddm::getHelper('core')->getEntityAttribute('news', 'url_key')) {
            $urlIndex = new Urlindex();
            $urlIndex->saveUrlIndex($newsId);
        }
        return $this;
    }

    protected function _afterSave(AbstractModel $model): static
    {
        parent::_afterSave($model);
        //必须在第一行
        if ($model->url_key != $model->getOrigData('url_key')) {
            //删掉旧的URL记录
            Url::singleton()->removeFromUrlKey('news', $model->getOrigData('url_key'));
            $this->saveUrlIndex($model->getId());
        }
        return $this;
    }
}
