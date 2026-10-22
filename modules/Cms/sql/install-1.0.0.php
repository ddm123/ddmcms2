<?php

/** @var \Core\Model\Install $this */
Ddm\Db::schema()->createIfNotExists('onepage', function (mixed $table) {
    $table->integer('onepage_id')->unsigned()->nullable(false)->autoIncrement();
    $table->integer('create_at')->unsigned()->nullable(false)->default(0);
    $table->integer('update_at')->unsigned()->nullable(false)->default(0);
    $table->primary(['onepage_id']);
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('widget', function (mixed $table) {
    $table->integer('widget_id')->unsigned()->nullable(false)->autoIncrement();
    $table->string('identifier', 128)->nullable(false)->default('');
    $table->integer('create_at')->unsigned()->nullable(false)->default(0);
    $table->integer('update_at')->unsigned()->nullable(false)->default(0);
    $table->primary(['widget_id']);
    $table->unique(['identifier'], 'widget_identifier');
});
Ddm\Db::schema()->createIfNotExists('cms_value_int', function (mixed $table) {
    $table->integer('entity_id')->unsigned()->nullable(false)->default(0);
    $table->integer('attribute_id')->unsigned()->nullable(false)->default(0);
    $table->integer('language_id')->unsigned()->nullable(false)->default(0);
    $table->integer('value')->unsigned()->nullable(true);
    $table->primary(['entity_id', 'attribute_id', 'language_id']);
    $table->index(['attribute_id'], 'cms_value_int_attribute_id');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('cms_value_varchar', function (mixed $table) {
    $table->integer('entity_id')->unsigned()->nullable(false)->default(0);
    $table->integer('attribute_id')->unsigned()->nullable(false)->default(0);
    $table->integer('language_id')->unsigned()->nullable(false)->default(0);
    $table->string('value', 255)->nullable(true);
    $table->primary(['entity_id', 'attribute_id', 'language_id']);
    $table->index(['attribute_id'], 'cms_value_int_attribute_id');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('cms_value_text', function (mixed $table) {
    $table->integer('entity_id')->unsigned()->nullable(false)->default(0);
    $table->integer('attribute_id')->unsigned()->nullable(false)->default(0);
    $table->integer('language_id')->unsigned()->nullable(false)->default(0);
    $table->text('value')->nullable(true);
    $table->primary(['entity_id', 'attribute_id', 'language_id']);
    $table->index(['attribute_id'], 'cms_value_int_attribute_id');
    $table->engine('InnoDB');
});
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'title')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'title', 'backend_type' => 'varchar', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => '标题', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '1'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'url_key')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'url_key', 'backend_type' => 'varchar', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => 'URL', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '5'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'is_enabled')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'is_enabled', 'backend_type' => 'int', 'backend_table' => 'cms', 'frontend_type' => 'radio', 'frontend_label' => '启用', 'source_model' => '\Core\Model\Attribute\Source\Yesno', 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '10'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'content')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'content', 'backend_type' => 'text', 'backend_table' => 'cms', 'frontend_type' => 'textarea', 'frontend_label' => '内容', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '15'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'cache_lifetime')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'cache_lifetime', 'backend_type' => 'decimal:2', 'backend_table' => 'cms', 'frontend_type' => 'select', 'frontend_label' => '缓存有效期', 'source_model' => null, 'is_required' => '0', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '20'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'meta_keywords')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'meta_keywords', 'backend_type' => 'varchar', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => 'SEO 关键词', 'source_model' => null, 'is_required' => '0', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '25'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('onepage', 'meta_description')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'onepage', 'attribute_code' => 'meta_description', 'backend_type' => 'varchar', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => 'SEO 描述', 'source_model' => null, 'is_required' => '0', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '30'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('widget', 'title')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'widget', 'attribute_code' => 'title', 'backend_type' => 'varchar', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => '标题', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '1'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('widget', 'identifier')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'widget', 'attribute_code' => 'identifier', 'backend_type' => 'static', 'backend_table' => 'cms', 'frontend_type' => 'text', 'frontend_label' => '标识符', 'source_model' => null, 'is_required' => '1', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '5'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('widget', 'content')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'widget', 'attribute_code' => 'content', 'backend_type' => 'text', 'backend_table' => 'cms', 'frontend_type' => 'textarea', 'frontend_label' => '内容', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '10'])->save();
}
if (!Ddm::getHelper('core')->getEntityAttribute('widget', 'cache_lifetime')) {
    $this->getModel('core', 'attribute')->addData(['entity_type' => 'widget', 'attribute_code' => 'cache_lifetime', 'backend_type' => 'decimal:2', 'backend_table' => 'cms', 'frontend_type' => 'select', 'frontend_label' => '缓存有效期', 'source_model' => null, 'is_required' => '0', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '20'])->save();
}
Cms\Model\Onepage::singleton()->removeAttributesCache('onepage');
//必须清除属性缓存, 因为属性是刚添加上去的
$this->getModel('cms', 'onepage')->load(1)->addData(['title' => '网站首页', 'url_key' => 'index.html', 'is_enabled' => '1', 'content' => '<style type="text/css">
.banner {padding:0;line-height:100%;}

.main-body {padding:20px 32px;border:1px solid #ebebeb;margin-bottom:2px;}

.about-us {padding-bottom:32px;padding-top:12px;}
.about-us h1 {margin:0 0 12px 0;font-size:22px;color:#6b9000;line-height:100%;}
.about-us .about-left {float:left;}
.about-us .about-right {margin-left:194px;padding-left:16px;margin-top:22px;border-left:1px dotted #555555;}

.news {width:49%;border-right:1px solid #CCCCCC;float:left;}
.news h1,.news-right h1 {margin:0 0 12px 0;font-size:22px;color:#6b9000;line-height:100%;}
.news dl {margin:0;padding:0 32px 0 0;}
.news dl dt {font-weight:bold;}
.news dl dd {color:#666666;margin:0 0 12px 0;padding:0 0 12px 0;border-bottom:1px dotted #CCCCCC;}
.news dl dd p {margin:0;text-align:right;}
.news-right {margin-left:50%;}
.news-right .content {padding-left:32px;}
</style>

    <div class="banner"><img src="{?echo $this->getImageUrl(\'banner.jpg\')?}" alt="" /></div>
    <div class="main-body">
        <div class="about-us">
            <h1>Welcome to our site</h1>
            <div class="about-left"><img src="{?echo $this->getImageUrl(\'pix1.jpg\')?}" alt="" /></div>
            <div class="about-right">Totam rem aperiam eaque ipsa.
Quae ab illo inventore veritatis et quasi architecto. Audantium, totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi. architecto. Sae ab illo inventore veritatis et quasi architecto. Audantium, totam rem aperiam eaque ipsa quae inventore  architecto... <a href="#">More</a></div>
        </div>
    </div>
    <div class="main-body">
        <div class="news">
            <h1>NEWS</h1>
            <dl>
                <dt>Totam rem aperiam eaque ipsa</dt>
                <dd>Quae ab illo inventore veritatis et quasi architecto audantium<p><a href="#">More</a></p></dd>
                <dt>Totam rem aperiam eaque ipsa</dt>
                <dd>Quae ab illo inventore veritatis et quasi architecto audantium<p><a href="#">More</a></p></dd>
                <dt>Totam rem aperiam eaque ipsa</dt>
                <dd>Quae ab illo inventore veritatis et quasi architecto audantium<p><a href="#">More</a></p></dd>
            </dl>
        </div>
        <div class="news-right">
            <div class="content">
                <h1>Our services</h1>
            </div>
        </div>
        <div class="clear"></div>
    </div>', 'cache_lifetime' => '0', 'meta_keywords' => 'DDMCMS', 'meta_description' => '首页'])->save();
Cms\Model\Widget::singleton()->removeAttributesCache('widget');
//必须清除属性缓存, 因为属性是刚添加上去的
$this->getModel('cms', 'widget')->load(1)->addData(['title' => '首页底部版权', 'identifier' => 'bottom_copyright', 'content' => 'Copyright 2014-2015. -  DDMCMS  - All rights reserved.'])->save();
$this->getModel('cms', 'widget')->load(2)->addData(['title' => '前台导航菜单', 'identifier' => 'menus', 'content' => '<ul>
    <li><a href="{?echo Ddm::getHomeUrl()?}">首页</a></li>
    <li><a href="#">关于我们</a></li>
    <li><a href="{?echo Ddm::getUrl(\'news\')?}">新闻中心</a></li>
    <li class="last"><a href="#">联系我们</a></li>
</ul>'])->save();
