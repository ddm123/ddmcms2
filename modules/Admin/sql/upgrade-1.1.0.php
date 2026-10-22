<?php

/* @var $this \Core\Model\Install */
Ddm\Db::schema()->table('admin_group', function (mixed $table) {
    $table->string('description', 255)->nullable(true)->after('group_name');
});
Ddm\Db::schema()->table('admin_user', function (mixed $table) {
    $table->string('description', 255)->nullable(true)->after('admin_pass');
});
$group = $this->getModel('admin', 'group');
$group->loadFromGroupName('Administrators');
if ($group->getId()) {
    $group->setData('description', '该组下的管理员对后台有不受限制的完全访问权')->save();
}
