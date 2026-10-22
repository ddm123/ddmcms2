<?php

namespace News\Model\Category;

use Core\Model\Attribute;
use Core\Model\Urlindex\AbstractModel;
use Ddm;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Urlindex extends AbstractModel
{

    protected mixed $_module = 'news';

    protected mixed $_controller = 'category';

    protected mixed $_action = 'view';

    protected mixed $_params = ['id' => '{entity_id}'];

    /**
     * @return Attribute
     */
    public function getUrlKeyAttribute(): Attribute
    {
        return $this->_urlKeyAttribute === null ? $this->_urlKeyAttribute = Ddm::getHelper('core')->getEntityAttribute('news_category', 'url_key') : $this->_urlKeyAttribute;
    }
}
