<?php

namespace Admin\Model\Resource;

use Admin\Model\Admin as ModelAdmin;
use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Resource\AbstractModel;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Admin extends AbstractModel
{

    protected function _init(): static
    {
        $this->_setTable('admin_user', 'admin_id');
        return $this;
    }

    /**
     * @param ModelAdmin $object
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login(ModelAdmin $object, string $username, string $password): bool
    {
        if ($username != '' && $password != '') {
            $select = Db::table($this->getMainTableName())->where('admin_name', '=', $username)->where('admin_pass', '=', $object->getPasswordHash($password))->limit(2);
            $result = $select->get();
            if (count($result) == 1) {
                $object->addData($result[0]);
                $object->setOrigData($result[0], null, true);
                return true;
            }
        }
        return false;
    }

    /**
     * 返回格式是array(group_id=>group_name[,...])的形式
     * @param int $adminId
     * @return array
     */
    public function getGroupsFromAdminId(int $adminId): array
    {
        return Db::table(['a' => 'admin_group_user'])->join(['b' => 'admin_group'], 'b.group_id', '=', 'a.group_id')->where('a.admin_id', '=', $adminId)->orderBy('a.position', 'ASC')->pluck('b.group_name', 'a.group_id');
    }

    /**
     * @param ModelAbstractModel $object
     * @return array
     */
    protected function _getSaveData(ModelAbstractModel $object): array
    {
        $dataValue = parent::_getSaveData($object);
        if (isset($dataValue['extra']) && is_array($dataValue['extra'])) {
            $dataValue['extra'] = serialize($dataValue['extra']);
        }
        return $dataValue;
    }
}
