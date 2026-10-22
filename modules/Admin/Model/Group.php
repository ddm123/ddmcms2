<?php

namespace Admin\Model;

use Core\Model\AbstractModel;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
/**
 * @method \Admin\Model\Resource\Group getResource()
 * @property string $group_name
 */
class Group extends AbstractModel
{

    public const ALLOW_TYPE_READ = 'read';

    public const ALLOW_TYPE_ADD = 'add';

    public const ALLOW_TYPE_EDIT = 'edit';

    public const ALLOW_TYPE_DELETE = 'delete';

    protected static mixed $_instance = null;

    protected array $_adminAllows = [];

    protected mixed $_allows = null;

    /**
     * 使用单例模式
     * @return \Admin\Model\Group
     */
    public static function singleton(): Group
    {
        return self::$_instance === null ? self::$_instance = new Group() : self::$_instance;
    }

    /**
     * @param int $adminId
     * @return array
     */
    public function getAllowsFromAdminId(int $adminId): array
    {
        if ($adminId = (int) $adminId) {
            if (!isset($this->_adminAllows[$adminId])) {
                $this->_adminAllows[$adminId] = [];
                $allows = $this->getResource()->getAllowsDataFromAdminId($adminId);
                foreach ($allows as $row) {
                    isset($this->_adminAllows[$adminId][$row['group_id']]) or $this->_adminAllows[$adminId][$row['group_id']] = [];
                    if ($row['path'] == 'all') {
                        $this->_adminAllows[$adminId][$row['group_id']] = $row['path'];
                    } else {
                        isset($this->_adminAllows[$adminId][$row['group_id']][$row['path']]) or $this->_adminAllows[$adminId][$row['group_id']][$row['path']] = [];
                        $this->_adminAllows[$adminId][$row['group_id']][$row['path']][$row['allow_type']] = (bool) $row['allow_value'];
                    }
                }
            }
            return $this->_adminAllows[$adminId];
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAllows(): array
    {
        if ($this->_allows === null) {
            $this->_allows = $this->getId() ? $this->getResource()->getAllowsFromGroupId($this->getId()) : [];
        }
        return $this->_allows;
    }

    /**
     * @param string $groupName
     * @return $this
     */
    public function loadFromGroupName(string $groupName): static
    {
        $this->getResource()->loadFromGroupName($this, $groupName);
        return $this;
    }

    /**
     * @param array $resources
     * @return $this
     */
    public function saveResources(array $resources): static
    {
        $this->getResource()->saveResources($this, $resources);
        return $this;
    }

    protected function _afterDelete(): static
    {
        Db::table('admin_allow')->where('group_id', '=', $this->getId())->delete();
        Db::table('admin_allow_value')->where('group_id', '=', $this->getId())->delete();
        return parent::_afterDelete();
    }
}
