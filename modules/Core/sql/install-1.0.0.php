<?php

/* @var $this \Core\Model\Install */
Ddm\Db::schema()->createIfNotExists('attribute', function (mixed $table) {
    $table->integer('attribute_id')->unsigned()->autoIncrement()->nullable(false);
    $table->string('entity_type', 64)->nullable(false)->default('');
    $table->string('attribute_code', 64)->nullable(false)->default('');
    $table->string('backend_type', 16)->nullable(false)->default('static');
    $table->string('backend_table', 32)->nullable(true);
    $table->string('frontend_type', 16)->nullable(false)->default('');
    $table->string('frontend_label', 225)->nullable(true);
    $table->string('source_model', 225)->nullable(true);
    $table->tinyInteger('is_required')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('is_global')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('is_system')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('is_visible')->unsigned()->nullable(false)->default(1);
    $table->string('note', 255)->nullable(true);
    $table->integer('position')->unsigned()->nullable(false)->default(0);
    $table->primary(['attribute_id']);
    $table->unique(['entity_type', 'attribute_code'], 'attribute_entity_type_attribute_code');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('url_index', function (mixed $table) {
    $table->string('url_path', 225)->nullable(false)->default('');
    $table->integer('language_id')->unsigned()->nullable(false)->default(0);
    $table->string('module', 64)->nullable(false)->default('');
    $table->string('controller', 64)->nullable(true);
    $table->string('action', 64)->nullable(true);
    $table->string('params', 225)->nullable(true);
    $table->primary(['url_path', 'language_id']);
    $table->engine('InnoDB');
});
