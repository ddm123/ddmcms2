<?php

/** @var \Core\Model\Install $this */
Ddm\Db::schema()->createIfNotExists('news_category', function (mixed $table) {
    $table->integer('category_id')->unsigned()->nullable(false)->autoIncrement();
    $table->integer('position')->unsigned()->nullable(false)->default(0);
    $table->integer('create_at')->unsigned()->nullable(false)->default(0);
    $table->integer('update_at')->unsigned()->nullable(false)->default(0);
    $table->primary(['category_id']);
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('news', function (mixed $table) {
    $table->integer('news_id')->unsigned()->nullable(false)->autoIncrement();
    $table->integer('category_id')->unsigned()->nullable(false)->default(0);
    $table->integer('views')->unsigned()->nullable(false)->default(0);
    $table->integer('update_at')->unsigned()->nullable(false)->default(0);
    $table->integer('create_at')->unsigned()->nullable(false)->default(0);
    $table->primary(['news_id']);
    $table->index(['category_id'], 'news_category_id');
    $table->engine('InnoDB');
});
$this->addAttribute([['entity_type' => 'news_category', 'attribute_code' => 'name', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => '名称', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '1'], ['entity_type' => 'news_category', 'attribute_code' => 'url_key', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => 'URL Key', 'source_model' => null, 'is_required' => '0', 'is_global' => '0', 'is_system' => '1', 'note' => '使用友好的URL显示链接', 'position' => '5'], ['entity_type' => 'news_category', 'attribute_code' => 'position', 'backend_type' => 'static', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => '排序', 'source_model' => null, 'is_required' => '1', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '10'], ['entity_type' => 'news', 'attribute_code' => 'category_id', 'backend_type' => 'static', 'backend_table' => 'news', 'frontend_type' => 'select', 'frontend_label' => '分类', 'source_model' => '\News\Model\Category\Option', 'is_required' => '1', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '0'], ['entity_type' => 'news', 'attribute_code' => 'title', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => '标题', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '5'], ['entity_type' => 'news', 'attribute_code' => 'url_key', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => 'URL Key', 'source_model' => null, 'is_required' => '0', 'is_system' => '1', 'is_global' => '0', 'note' => '使用友好的URL显示链接', 'position' => '10'], ['entity_type' => 'news', 'attribute_code' => 'from', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => '来源', 'source_model' => null, 'is_required' => '0', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '15'], ['entity_type' => 'news', 'attribute_code' => 'author', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => '作者', 'source_model' => null, 'is_required' => '0', 'is_global' => '1', 'is_system' => '1', 'note' => null, 'position' => '20'], ['entity_type' => 'news', 'attribute_code' => 'content', 'backend_type' => 'text', 'backend_table' => 'news', 'frontend_type' => 'editor', 'frontend_label' => '内容', 'source_model' => null, 'is_required' => '1', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '25'], ['entity_type' => 'news', 'attribute_code' => 'meta_keywords', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => 'SEO 关键词', 'source_model' => null, 'is_required' => '0', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '30'], ['entity_type' => 'news', 'attribute_code' => 'meta_description', 'backend_type' => 'varchar', 'backend_table' => 'news', 'frontend_type' => 'text', 'frontend_label' => 'SEO 描述', 'source_model' => null, 'is_required' => '0', 'is_global' => '0', 'is_system' => '1', 'note' => null, 'position' => '35']]);
