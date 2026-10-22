<?php

namespace Cms\Block\Onepage;

use Cms\Model\Onepage;
use Cms\Model\Processed\Template;
use Core\Block\AbstractModel;
use Ddm;
use Ddm\Request;
use Ddm\StringHelper;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class View extends AbstractModel
{

    protected mixed $_onepageData = null;

    protected mixed $_onepageId = null;

    public function init(): static
    {
        if ($this->getOnepageData()) {
            $this->getTemplateObject()->setTitle($this->_onepageData['title'])->addMeta('description', StringHelper::singleton()->escapeHtml($this->_onepageData['meta_description']))->addMeta('keywords', StringHelper::singleton()->escapeHtml($this->_onepageData['meta_keywords']));
            if ($this->_onepageData['add_css']) {
                foreach ($this->_onepageData['add_css'] as $file) {
                    $this->getTemplateObject()->addCss($file);
                }
            }
            if ($this->_onepageData['add_js']) {
                foreach ($this->_onepageData['add_js'] as $file) {
                    $this->getTemplateObject()->addJs($file);
                }
            }
            if ($this->_onepageData['styles']) {
                foreach ($this->_onepageData['styles'] as $style) {
                    $this->getTemplateObject()->addStyle($style);
                }
            }
        }
        return parent::init();
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setOnepageId(int $id): static
    {
        $this->_onepageId = (int) $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getOnepageId(): int
    {
        if ($this->_onepageId === null) {
            $this->_onepageId = intval(Request::get('id')) or $this->_onepageId = intval(Ddm::getConfig()->getConfigValue('web/pages/not_found'));
        }
        return $this->_onepageId;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOnepageData(?string $name = null): mixed
    {
        if ($this->_onepageData === null) {
            Ddm::dispatchEvent('get_onepage_data_before', ['onepage' => $this]);
            $id = $this->getOnepageId();
            if (!$id) {
                return $this->_onepageData = false;
            }
            $this->_onepageData = Ddm::getHelper('Cms')->getOnepageCache($id);
            //先看是否已经存在缓存
            if ($this->_onepageData === false) {
                //如果未被缓存过
                $onepage = new Onepage();
                $onepage->setLanguageId(Ddm::getLanguage()->language_id)->load($id);
                if ($onepage->getId()) {
                    $this->_processedContent($onepage);
                    $onepage->meta_keywords or $onepage->meta_keywords = $onepage->title;
                    $onepage->meta_description or $onepage->meta_description = StringHelper::singleton()->cutString($onepage->content, 200, '...', true);
                    $this->_onepageData = $onepage->getData();
                    Ddm::getHelper('Cms')->saveOnepageCache($onepage);
                    //保存到缓存
                }
            }
            Ddm::dispatchEvent('get_onepage_data_after', ['onepage' => $this, 'data' => $this->_onepageData]);
        }
        return $name === null ? $this->_onepageData : ($this->_onepageData && isset($this->_onepageData[$name]) ? $this->_onepageData[$name] : null);
    }

    /**
     * @param Onepage $onepage
     * @return $this
     */
    protected function _processedContent(Onepage $onepage): static
    {
        $template = new Template($onepage->content, ['this' => $this, 'onepage' => $onepage, 'template_object' => $this->getTemplateObject()]);
        $template->isGetStyle(true);
        $onepage->content = $template->processed();
        $onepage->add_css = $template->getCssFiles();
        $onepage->add_js = $template->getJsFiles();
        $onepage->styles = $template->getStyles();
        return $this;
    }

    protected function _toHtml(): string
    {
        return $this->getOnepageData('content');
    }
}
