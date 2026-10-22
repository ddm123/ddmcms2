<?php

/* @var $this \Core\Model\Install */
Ddm\Db::schema()->createIfNotExists('admin_group', function (mixed $table) {
    $table->integer('group_id')->unsigned()->nullable(false)->autoIncrement();
    $table->string('group_name', 25)->nullable(false)->default('');
    $table->primary(['group_id']);
    $table->unique(['group_name'], 'admin_group_group_name');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('admin_user', function (mixed $table) {
    $table->integer('admin_id')->unsigned()->nullable(false)->autoIncrement();
    $table->string('admin_name', 50)->nullable(false)->default('');
    $table->string('admin_pass', 32)->nullable(false)->default('');
    $table->integer('loginnum')->unsigned()->nullable(false)->default(0);
    $table->integer('logintime')->unsigned()->nullable(false)->default(0);
    $table->integer('prevtime')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('is_active')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('edit_name')->unsigned()->nullable(false)->default(0);
    $table->tinyInteger('edit_pass')->unsigned()->nullable(false)->default(0);
    $table->text('extra')->nullable(true);
    $table->primary(['admin_id']);
    $table->unique(['admin_name'], 'admin_name');
    //$table->index(['admin_name', 'admin_pass'], 'admin_user_name_pass');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('admin_group_user', function (mixed $table) {
    $table->integer('group_id')->unsigned()->nullable(false)->default(0);
    $table->integer('admin_id')->unsigned()->nullable(false)->default(0);
    $table->integer('position')->unsigned()->nullable(false)->default(0);
    $table->primary(['group_id', 'admin_id']);
    $table->index(['position'], 'admin_group_user_position');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('admin_allow', function (mixed $table) {
    $table->integer('allow_id')->unsigned()->nullable(false)->autoIncrement();
    $table->integer('group_id')->unsigned()->nullable(false)->default(0);
    $table->string('path', 200)->nullable(false)->default('');
    $table->primary(['allow_id']);
    $table->unique(['group_id', 'path'], 'admin_allow_group_id_path');
    $table->engine('InnoDB');
});
Ddm\Db::schema()->createIfNotExists('admin_allow_value', function (mixed $table) {
    $table->integer('allow_id')->unsigned()->nullable(false)->default(0);
    $table->integer('group_id')->unsigned()->nullable(false)->default(0);
    $table->string('allow_type', 16)->nullable(false)->default('read');
    $table->tinyInteger('allow_value')->unsigned()->nullable(false)->default(0);
    $table->primary(['allow_id', 'allow_type']);
    $table->index(['group_id'], 'admin_allow_value_group_id');
    $table->engine('InnoDB');
});
$group = $this->getModel('admin', 'group');
$group->loadFromGroupName('Administrators');
if (!$group->getId()) {
    $group->load(1);
}
$group->addData('group_name', 'Administrators')->save()->saveResources('all');
