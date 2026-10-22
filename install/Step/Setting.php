<?php

namespace Step;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Setting extends AbstractModel
{

    protected ?string $_template = 'setting.phtml';

    private ?array $_postData = null;

    private ?array $_elements = null;

    /**
     * @return array<string, string>
     */
    public function getDbDrivers(): array
    {
        $drivers = [];
        if (extension_loaded('pdo_mysql')) {
            $drivers['\Ddm\Db\PDO\Mysql'] = 'MySQL';
        }
        if (extension_loaded('pdo_sqlite')) {
            $drivers['\Ddm\Db\PDO\Sqlite'] = 'SQLite';
        }
        return $drivers;
    }

    /**
     * @return array<string, string>
     */
    public function getCacheDrivers(): array
    {
        $drivers = [];
        if (extension_loaded('apc')) {
            $drivers['\Ddm\Cache\Apc'] = 'Apc';
        }
        $drivers['\Ddm\Cache\Redis'] = 'Redis';
        $drivers['\Ddm\Cache\File'] = 'File';
        if (extension_loaded('pdo_sqlite')) {
            $drivers['\Ddm\Cache\Sqlite'] = 'SQLite';
        }
        return $drivers;
    }

    /**
     * @param string $key
     * @param bool $escape
     * @return string|null
     */
    public function getPost(string $key, bool $escape = false): ?string
    {
        $result = $this->_postData && isset($this->_postData[$key]) ? $this->_postData[$key] : null;
        return $escape && $result ? htmlspecialchars($result) : $result;
    }

    /**
     * @return array
     */
    public function getElements(): array
    {
        if ($this->_elements === null) {
            $this->_elements = [['label' => '数据库', 'elements' => ['db_driver' => ['label' => '数据库类型', 'type' => 'select', 'values' => $this->getDbDrivers(), 'value' => $this->getPost('db_driver')], 'db_host' => ['label' => '主机', 'type' => 'text', 'value' => $this->getPost('db_host') ? $this->getPost('db_host') : 'localhost'], 'db_port' => ['label' => '端口', 'type' => 'text', 'value' => $this->getPost('db_port') ? (int) $this->getPost('db_port') : '3306'], 'db_username' => ['label' => '用户名', 'type' => 'text', 'value' => $this->getPost('db_username', true)], 'db_password' => ['label' => '密码', 'type' => 'password', 'value' => $this->getPost('db_password', true)], 'db_name' => ['label' => '数据库名', 'type' => 'text', 'value' => $this->getPost('db_name')], 'db_tablepre' => ['label' => '表名前缀', 'type' => 'text', 'value' => $this->getPost('db_tablepre') ? $this->getPost('db_tablepre') : 'ddmcms_', 'notice' => '建议修改为一个不容易让别人猜到的前缀']]], ['label' => '后台管理员', 'elements' => ['admin_username' => ['label' => '用户名', 'type' => 'text', 'value' => $this->getPost('admin_username', true)], 'admin_password' => ['label' => '密码', 'type' => 'password', 'value' => $this->getPost('admin_password', true)], 'admin_password2' => ['label' => '确认密码', 'type' => 'password', 'value' => $this->getPost('admin_password', true), 'notice' => '重复再输入一次密码, 防止输入错误']]], ['label' => '其它', 'elements' => ['other_adminpath' => ['label' => '后台登录入口', 'type' => 'text', 'value' => $this->getPost('other_adminpath') ? $this->getPost('other_adminpath') : 'admincp', 'notice' => '不是很建议你保留默认'], 'other_cache_driver' => ['label' => '缓存保存在', 'type' => 'select', 'values' => $this->getCacheDrivers(), 'value' => $this->getPost('other_cache_driver'), 'notice' => '如果你不知道选择哪个, 请保留默认'], 'other_cache_prefix' => ['label' => '缓存键名前缀', 'type' => 'text', 'value' => $this->getPost('other_cache_prefix') ? $this->getPost('other_cache_prefix') : 'ddmcms_', 'notice' => '如果你有多个站使用同一种缓存, 例如: APC或Memcache等, 就很有必要设一个前缀以避免冲突']]]];
        }
        return $this->_elements;
    }

    /**
     * @param string $name
     * @param array $attributes
     * @return string
     */
    public function getElementHtml(string $name, array $attributes): string
    {
        $html = '<label for="' . $name . '">' . $attributes['label'] . '</label>';
        if ($attributes['type'] == 'select') {
            $html .= '<select name="' . $name . '" id="' . $name . '">';
            foreach ($attributes['values'] as $value => $label) {
                $html .= '<option value="' . $value . '"' . ($attributes['value'] == $value ? ' selected="selected"' : '') . '>' . $label . '</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input type="' . ($attributes['type'] == 'password' ? 'password' : 'text') . '" name="' . $name . '" id="' . $name . '" value="' . $attributes['value'] . '" class="text" />';
        }
        empty($attributes['notice']) or $html .= ' (' . $attributes['notice'] . ')';
        return $html;
    }

    protected function _beforeRun(): static
    {
        $this->clearCache();
        $this->addTitle('配置数据库');
        if (is_file(SITE_ROOT . '/data/setting-post-data.tmp')) {
            $this->_postData = unserialize(file_get_contents(SITE_ROOT . '/data/setting-post-data.tmp'));
        }
        return parent::_beforeRun();
    }
}
