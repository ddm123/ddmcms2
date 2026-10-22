<?php

namespace News\Block\Adminhtml\Category;

use Admin\Block\Widget\Form;
use Ddm;
use Ddm\Request;
use News\Model\Category;
use News\Model\Category\Option;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Edit extends Form
{

    protected bool $_languageId = false;

    protected mixed $_categories = null;

    /**
     * @return $this
     */
    public function init(): static
    {
        parent::init();
        $this->getCategory() and $id = $this->getCategory()->getId() or $id = false;
        $this->formAction = Ddm::getUrl('*/*/save', ['id' => $id, 'language' => $this->getLanguageId()]);
        $this->method = 'post';
        $this->formId = 'edit-category-form';
        $this->titleText = $id ? Ddm::getTranslate('news')->translate('修改分类') : Ddm::getTranslate('news')->translate('增加分类');
        $this->setTemplate('category/edit.phtml');
        $this->addButton('save_continue_edit', ['label' => Ddm::getTranslate('admin')->translate('保存并继续编辑'), 'onclick' => 'saveAndContinueEdit(editCategoryForm)', 'class' => 'btn-primary']);
        if ($id) {
            $this->addLanguageSwitchBlock('language');
        }
        $this->removeButton('back');
        if (Ddm::getHelper('core')->getEntityAttribute('news_category', 'url_key')) {
            $this->titleRightHtml = '<a class="btn" href="' . Ddm::getLanguage()->getUrl('*/*/refresh-url-index') . '" onclick="return refreshUrlIndex(this)">' . Ddm::getTranslate()->___('刷新%sURL索引', Ddm::getTranslate()->translate('分类')) . '</a>';
            $javascrip = 'function refreshUrlIndex(e){' . 'if(!window.refreshUrlIndexWindow)window.refreshUrlIndexWindow = msg_box("' . Ddm::getTranslate('core')->translate('正在刷新URL索引, <br />请勿关闭或刷新浏览器') . '... <span id=\"refresh-url-index-status\"></span>","' . Ddm::getTranslate('core')->___('刷新%sURL索引', Ddm::getTranslate('admin')->translate('分类')) . '",false);' . 'Ajax().post(typeof e == "string" ? e : e.getAttribute("href"),null,function(xhr,result){' . 'if(result && result.url!=""){$id("refresh-url-index-status").innerHTML = Math.floor((result.from+result.limit)/result.maxId*100)+"%";refreshUrlIndex(result.url);}' . 'else{window.refreshUrlIndexWindow.close();window.refreshUrlIndexWindow = null;msg_box("' . Ddm::getTranslate('core')->translate('已完成') . '","' . Ddm::getTranslate('core')->___('刷新%sURL索引', Ddm::getTranslate('admin')->translate('分类')) . '");}' . '},"json");' . 'return false;}';
            $this->addJs($javascrip);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->_languageId === false ? $this->_languageId = (int) Request::get('language', 'int', '0') : $this->_languageId;
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        if ($this->_categories === null) {
            $this->_categories = Option::singleton()->getAllOptionsFromLanguage(false, $this->getLanguageId());
            $newsCount = Category::singleton()->getResource()->getNewsCount();
            foreach ($this->_categories as $key => $item) {
                $this->_categories[$key]['count'] = isset($newsCount[$item['value']]) ? $newsCount[$item['value']] : 0;
            }
        }
        return $this->_categories;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return Ddm::registry('category');
    }

    protected function _prepareElements(): static
    {
        $category = $this->getCategory();
        foreach (Category::singleton()->getAttributes() as $attribute) {
            if ($attribute->is_visible) {
                $this->addElement($attribute->attribute_code, $this->_getElementConfig($attribute, $category));
            }
        }
        return parent::_prepareElements();
    }

    /**
     * @param array $attribute
     * @param Category $category
     * @return array
     */
    protected function _getElementConfig(array $attribute, Category $category): array
    {
        $config = ['label' => Ddm::getTranslate('news')->translate($attribute->frontend_label), 'id' => 'category-' . $attribute->attribute_code, 'type' => $attribute->frontend_type, 'name' => 'category[' . $attribute->attribute_code . ']', 'verify' => $attribute->is_required ? 1 : null, 'value' => $category ? $category->{$attribute->attribute_code} : '', 'notice' => $attribute->note ? Ddm::getTranslate('news')->translate($attribute->note) : null, 'style' => $attribute->frontend_type == 'text' || $attribute->frontend_type == 'textarea' ? 'width:99%;' : null];
        if ($attribute->frontend_type == 'radio') {
            if ($attribute->source_model) {
                $config['list'] = call_user_func([$attribute->source_model, 'singleton'])->getAllOptions(false, false);
            }
            $config['type'] = 'radiogroup';
        }
        if ($attribute->attribute_code == 'position') {
            $config['verify'] = 'int';
            $config['errormsg'] = Ddm::getTranslate('admin')->translate('请填写一个正整数');
            unset($config['style']);
            if (!$this->getCategory()) {
                $config['value'] = Category::singleton()->getMaxPosition();
            }
        }
        if (!$attribute->is_global && $this->getLanguageId() && $category) {
            isset($config['after_html']) or $config['after_html'] = '';
            $config['after_html'] .= ' &nbsp; <input type="checkbox" name="use_default[]" value="' . $attribute->attribute_code . '"';
            if ($category->isUseDefaultValue($attribute->attribute_code)) {
                $config['after_html'] .= ' checked="checked"';
                $config['disabled'] = 'disabled';
            }
            $config['after_html'] .= ' id="' . $config['id'] . '-usedefault" onclick="setUseDefaultValue(this,\'' . $config['id'] . '\',\'' . $config['type'] . '\')" />';
            $config['after_html'] .= '<label for="' . $config['id'] . '-usedefault">' . Ddm::getTranslate('core')->translate('使用默认值') . '</label>';
        }
        return $config;
    }
}
