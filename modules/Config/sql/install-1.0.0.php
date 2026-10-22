<?php

/* @var $this \Core\Model\Install */
Ddm\Db::schema()->createIfNotExists('config', function (mixed $table) {
    $table->integer('config_id')->unsigned()->autoIncrement()->nullable(false);
    $table->string('path', 255)->nullable(false)->default('');
    $table->primary(['config_id']);
    $table->unique(['path'], 'config_path');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('config_value', function (mixed $table) {
    $table->integer('config_value_id')->unsigned()->autoIncrement()->nullable(false);
    $table->integer('config_id')->unsigned()->nullable(false)->default(0);
    $table->integer('language_id')->unsigned()->nullable(false)->default(0);
    $table->text('config_value')->nullable(true);
    $table->primary(['config_value_id']);
    $table->unique(['config_id', 'language_id'], 'config_value_config_id_language_id');
});
$this->getModel('config', 'config')->addData(['path' => 'web/language/inc_url', 'values' => [0 => '1']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/language/default_language', 'values' => [0 => '0']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/language/auto_saved', 'values' => [0 => '1']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/design/frontend_theme', 'values' => [0 => 'default']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/design/admin_theme', 'values' => [0 => 'default']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/cookie/cookie_prefix', 'values' => [0 => 'ddmcms_']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/cookie/cookie_path', 'values' => [0 => '/']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/cookie/cookie_httponly', 'values' => [0 => '0']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/date/datetime_format', 'values' => [0 => 'Y-m-d H:i:s']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/date/date_format', 'values' => [0 => 'Y-m-d']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/currency/currency_seat', 'values' => [0 => 1]])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/currency/currency_symbol', 'values' => [0 => '¥']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/currency/dec_point', 'values' => [0 => '.']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/currency/precision', 'values' => [0 => '2']])->save();
$this->getModel('config', 'config')->addData(['path' => 'system/currency/thousands_sep', 'values' => [0 => ',']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/pages/home', 'values' => [0 => '1']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/base/web_name', 'values' => [0 => '欢迎使用DDMCMS']])->save();
$this->getModel('config', 'config')->addData(['path' => 'web/base/hide_indexphp', 'values' => [0 => '1']])->save();
$this->addConfig('system/access/no_access_message', '403');
