<?php

/* @var $this \Core\Model\Install */
Ddm\Db::schema()->createIfNotExists('language', function (mixed $table) {
    $table->integer('language_id')->unsigned()->nullable(false)->autoIncrement();
    $table->string('language_code', 25)->nullable(false)->default('');
    $table->string('language_name', 32)->nullable(false)->default('');
    $table->tinyInteger('is_enable')->unsigned()->nullable(false)->default(0);
    $table->integer('position')->unsigned()->nullable(false)->default(0);
    $table->primary(['language_id']);
    $table->unique(['language_code'], 'language_language_code');
    $table->index(['is_enable'], 'language_is_enable');
    $table->engine('InnoDB');
});
$this->addConfig('web/language/default_language', '0');
$this->addConfig('web/language/default_code', 'zh_cn');
$this->addConfig('web/language/default_name', '简体中文');
