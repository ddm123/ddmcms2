<?php

namespace Config\Model\Resource;

use Config\Model\Config as ModelConfig;
use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Resource\AbstractModel;
use Ddm\Db;
use Ddm\Db\Expression;
use Ddm\Db\JoinClause;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Config extends AbstractModel
{

    protected function _init(): static
    {
        $this->_setTable('config', 'config_id');
        return $this;
    }

    /**
     * @param string $path
     * @param int $languageId
     * @return string
     */
    public function getConfigValue(string $path, int $languageId): string
    {
        $languageId = (int) $languageId;
        $select = Db::table(['a' => $this->getMainTableName()]);
        $select->leftJoin(['b' => 'config_value'], function (JoinClause $join) {
            $join->on('b.config_id', '=', 'a.config_id');
            $join->where('b.language_id', '=', 0);
        });
        if ($languageId) {
            $select->leftJoin(['c' => 'config_value'], function (JoinClause $join) use ($languageId) {
                $join->on('c.config_id', '=', 'a.config_id');
                $join->where('c.language_id', '=', $languageId);
            });
            $column = new Expression('IFNULL(c.config_value,b.config_value)');
        } else {
            $column = 'b.config_value';
        }
        $select->where('a.path', '=', $path);
        $value = $select->value($column);
        return $value === false ? null : $value;
    }

    /**
     * @param int $languageId
     * @return array
     */
    public function getConfigs(int $languageId): array
    {
        $languageId = (int) $languageId;
        $select = Db::table(['a' => $this->getMainTableName()]);
        $select->leftJoin(['b' => 'config_value'], function (JoinClause $join) {
            $join->on('b.config_id', '=', 'a.config_id');
            $join->where('b.language_id', '=', 0);
        });
        if ($languageId) {
            $select->leftJoin(['c' => 'config_value'], function (JoinClause $join) use ($languageId) {
                $join->on('c.config_id', '=', 'a.config_id');
                $join->where('c.language_id', '=', $languageId);
            });
            $column = new Expression('IFNULL(c.config_value,b.config_value)');
        } else {
            $column = 'b.config_value';
        }
        return $select->pluck($column, 'a.path');
    }

    /**
     * @param string $path
     * @return $this
     */
    public function removeConfigValue(string $path): static
    {
        $mainTable = $this->getMainTableName();
        Db::transaction(function () use ($mainTable, $path) {
            $builder = Db::table(['a' => $mainTable]);
            $sql = 'DELETE FROM ' . $builder->wrap(Db::getTable('config_value'));
            $sql .= ' WHERE config_id IN(';
            $sql .= 'SELECT a.config_id FROM ' . $builder->wrap($mainTable) . ' AS a WHERE a.path=?';
            $sql .= ')';
            $stmt = Db::getWriteConn()->query($sql, [$path]);
            if ($stmt->rowCount()) {
                $builder->where('path', '=', $path);
                $builder->delete();
            }
        });
        return $this;
    }

    /**
     * @param int $languageId
     * @return array
     */
    public function getConfigsFromLanguageId(int $languageId): array
    {
        $languageId = (int) $languageId;
        $select = Db::table(['a' => $this->getMainTableName()]);
        $select->leftJoin(['b' => 'config_value'], function (JoinClause $join) {
            $join->on('b.config_id', '=', 'a.config_id');
            $join->where('b.language_id', '=', 0);
        });
        if ($languageId) {
            $select->leftJoin(['c' => 'config_value'], function (JoinClause $join) use ($languageId) {
                $join->on('c.config_id', '=', 'a.config_id');
                $join->where('c.language_id', '=', $languageId);
            });
            $columns = ['a.path', 'default_value' => 'b.config_value', 'language_value' => 'c.config_value'];
        } else {
            $columns = ['a.path', 'default_value' => 'b.config_value', 'language_value' => 'b.config_value'];
        }
        return $select->get($columns, 'path');
    }

    /**
     * @param string $path
     * @return array
     */
    public function getConfigsFromPath(string $path): array
    {
        $select = Db::table(['a' => 'config_value']);
        $select->join(['b' => $this->getMainTableName()], 'b.config_id', '=', 'a.config_id');
        $select->where('b.path', '=', $path);
        return $select->pluck('a.config_value', 'a.language_id');
    }

    /**
     * @param int $configId
     * @return array
     */
    public function getConfigsFromConfigId(int $configId): array
    {
        $select = Db::table('config_value');
        $select->where('config_id', '=', (int) $configId);
        return $select->pluck('config_value', 'language_id');
    }

    /**
     * @param ModelConfig $config
     * @param string $path
     * @return $this
     */
    public function loadFromPath(ModelConfig $config, string $path): static
    {
        $select = Db::table(['a' => $this->getMainTableName()]);
        $select->leftJoin(['b' => 'config_value'], 'b.config_id', '=', 'a.config_id');
        $select->where('a.path', '=', $path);
        if ($data = $select->get(['a.config_id', 'a.path', 'b.language_id', 'b.config_value'])) {
            $_data = ['values' => []];
            foreach ($data as $row) {
                isset($_data['config_id']) or $_data['config_id'] = $row['config_id'];
                isset($_data['path']) or $_data['path'] = $row['path'];
                if ('' !== (string) $row['language_id']) {
                    $_data['values'][$row['language_id']] = $row['config_value'];
                }
            }
            if (!$_data['values']) {
                unset($_data['values']);
            }
            $config->addData($_data);
        }
        return $this;
    }

    protected function _afterSave(ModelAbstractModel $object): static
    {
        parent::_afterSave($object);
        if (($id = (int) $object->getId()) && is_array($values = $object->getData('values'))) {
            foreach ($values as $languageId => $value) {
                if ($value === false) {
                    Db::table('config_value')->where('config_id', '=', $id)->where('language_id', '=', $languageId)->delete();
                } else {
                    Db::getWriteConn()->insert(Db::getTable('config_value'), ['config_id' => $id, 'language_id' => $languageId, 'config_value' => $value], false, ['config_value' => $value]);
                }
            }
        }
        return $this;
    }

    protected function _afterDelete(ModelAbstractModel $object): static
    {
        Db::table('config_value')->where('config_id', '=', (int) $object->getId())->delete();
        return $this;
    }
}
