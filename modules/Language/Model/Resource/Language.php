<?php

namespace Language\Model\Resource;

use Core\Model\AbstractModel as ModelAbstractModel;
use Core\Model\Resource\AbstractModel;
use Ddm\Db;
use Ddm\Db\JoinClause;
use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Language extends AbstractModel
{

    protected function _init(): static
    {
        $this->_setTable('language', 'language_id');
        return $this;
    }

    /**
     * @param bool $isEnable
     * @return array
     */
    public function getAllLanguage(bool $isEnable = true): array
    {
        $builder = Db::table($this->getMainTableName())->orderBy('position', 'ASC');
        if ($isEnable) {
            $builder->where('is_enable', '=', 1);
        }
        return $builder->get(['language_id', 'language_code', 'language_name', 'position'], 'language_code');
    }

    /**
     * 根据language_id或language_code查询一条记录
     * @param int|string $languageId ID或Code
     * @param bool $isEnable 是否只查询状态为启用的记录
     * @return array|false
     */
    public function loadById(int|string $languageId, bool $isEnable = true): array|false
    {
        $select = Db::table($this->getMainTableName());
        $select->where(is_numeric($languageId) ? 'language_id' : 'language_code', '=', $languageId)->limit(1);
        $isEnable and $select->where('is_enable', '=', 1);
        return $select->first();
    }

    /**
     * @return int|null
     */
    public function getIdFromHost(): ?int
    {
        $host = Request::server()->HTTP_HOST or $host = Request::server()->SERVER_NAME;
        $select = Db::table(['a' => 'config_value']);
        $select->join(['b' => 'config'], function (JoinClause $join) {
            $join->on('b.config_id', '=', 'a.config_id');
            $join->where('b.path', '=', 'web/base/web_url');
        });
        $select->where('a.config_value', 'like', "%/{$host}/%");
        $select->limit(1);
        return $select->value('a.language_id');
    }

    /**
     * @return array
     */
    public function getAllBaseUrl(): array
    {
        $select = Db::table(['a' => 'config_value']);
        $select->join(['b' => 'config'], function (JoinClause $join) {
            $join->on('b.config_id', '=', 'a.config_id');
            $join->where('b.path', '=', 'web/base/web_url');
        });
        return $select->pluck('a.config_value', 'a.language_id');
    }
}
