<?php

namespace Admin\Model\Resource;

use Admin\Model\Group as ModelGroup;
use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Resource\AbstractModel;
use Ddm;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Group extends AbstractModel
{

    protected function _init(): static
    {
        $this->_setTable('admin_group', 'group_id');
        return $this;
    }

    /**
     * 返回格式是array(group_id=>group_name[,...])的形式
     * @param int $groupId
     * @return array
     */
    public function getAllGroups(?int $groupId = null): array
    {
        $builder = Db::table(['a' => $this->getMainTableName()]);
        $builder->orderBy('a.group_id', 'ASC');
        if ($groupId !== null) {
            $builder->where('a.group_id', '=', (int) $groupId);
        }
        return $builder->pluck('a.group_name', 'a.group_id');
    }

    /**
     * @param int $adminId
     * @return array
     */
    public function getAllowsDataFromAdminId(int $adminId): array
    {
        $select = Db::table(['gu' => 'admin_group_user']);
        $select->join(['allow' => 'admin_allow'], 'allow.group_id', '=', 'gu.group_id');
        $select->leftJoin(['v' => 'admin_allow_value'], 'v.allow_id', '=', 'allow.allow_id');
        $select->where('gu.admin_id', '=', $adminId);
        $select->orderBy('gu.position', 'ASC');
        return $select->get(['gu.group_id', 'allow.path', 'v.allow_type', 'v.allow_value']);
    }

    /**
     * @param int $groupId
     * @return array|string
     */
    public function getAllowsFromGroupId(int $groupId): array|string
    {
        $allows = [];
        $select = Db::table(['allow' => 'admin_allow']);
        $select->leftJoin(['v' => 'admin_allow_value'], 'v.allow_id', '=', 'allow.allow_id');
        $select->where('allow.group_id', '=', $groupId);
        $result = $select->get(['allow.path', 'v.allow_type', 'v.allow_value']);
        if ($result && $result[0]['path'] == 'all') {
            return $result[0]['path'];
        }
        foreach ($result as $row) {
            isset($allows[$row['path']]) or $allows[$row['path']] = [];
            $allows[$row['path']][$row['allow_type']] = (bool) $row['allow_value'];
        }
        return $allows;
    }

    /**
     * @param ModelGroup $object
     * @param string $groupName
     * @return $this
     */
    public function loadFromGroupName(ModelGroup $object, string $groupName): static
    {
        $this->load($object, $groupName, 'group_name');
        return $this;
    }

    /**
     * @param ModelGroup $object
     * @param array $resources
     * @return $this
     */
    public function saveResources(ModelGroup $object, array $resources): static
    {
        if ($groupId = $object->getId()) {
            if ($resources == 'all') {
                Db::table('admin_allow_value')->where('group_id', '=', $groupId)->delete();
                Db::table('admin_allow')->where('group_id', '=', $groupId)->where('path', '!=', 'all')->delete();
                Db::getWriteConn()->insert(Db::getTable('admin_allow'), ['group_id' => $groupId, 'path' => 'all'], false, ['path' => 'all']);
            } elseif (is_array($resources)) {
                Db::table('admin_allow')->where('group_id', '=', $groupId)->where('path', '=', 'all')->delete();
                foreach ($resources as $path => $allowValue) {
                    if (is_array($allowValue) && preg_match('/^\w[\w\/]*\w$/', $path)) {
                        $allowId = Db::table('admin_allow')->where('group_id', '=', $groupId)->where('path', '=', $path)->value('allow_id');
                        if (!$allowId) {
                            $allowId = Db::table('admin_allow')->insertGetId(['group_id' => $groupId, 'path' => strtolower($path)]);
                            if (!$allowId) {
                                throw new \Exception(Ddm::getTranslate('admin')->translate('保存权限失败，获取不到权限ID'));
                            }
                        }
                        foreach ($allowValue as $allowType => $value) {
                            if (preg_match('/^\w+$/', $allowType)) {
                                if ($value === '') {
                                    Db::table('admin_allow_value')->where('allow_id', '=', $allowId)->where('allow_type', '=', $allowType)->delete();
                                } else {
                                    $value = (int) $value ? 1 : 0;
                                    Db::getWriteConn()->insert(Db::getTable('admin_allow_value'), ['allow_id' => $allowId, 'group_id' => $groupId, 'allow_type' => $allowType, 'allow_value' => $value], false, ['allow_value' => $value]);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param int $groupId
     * @return int
     */
    public function getUserCount(int $groupId): int
    {
        if ($groupId = (int) $groupId) {
            return Db::table('admin_group_user')->where('group_id', '=', $groupId)->count();
        }
        return 0;
    }

    protected function _beforeDelete(ModelAbstractModel $object): static
    {
        parent::_beforeDelete($object);
        if ($this->getUserCount($object->getId())) {
            throw new \Exception(Ddm::getTranslate('admin')->translate('不允许删除，因为该管理员组下有管理员'));
        }
        return $this;
    }
}
