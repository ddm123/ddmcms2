<?php

namespace Step;

use Admin\Model\Admin;
use Core\Model\Install;
use Ddm;
use Ddm\Db;
use Ddm\Db\Schema\Table;
use Ddm\Request;

class Setup extends AbstractModel
{

    protected ?string $_template = 'setup.phtml';

    /**
     * @return $this
     */
    private function _saveConfig(): static
    {
        $xml = str_replace([
            '{admin_path}',
            '{hash_key}',
            '{db_tablepre}',
            '{db_driver}',
            '{db_host}',
            '{db_port}',
            '{db_username}',
            '{db_password}',
            '{db_dbname}',
            '{cache_driver}',
            '{cache_prefix}'
        ], [
            $_POST['other_adminpath'],
            md5($_SERVER['HTTP_HOST'].'-'.(isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['HTTP_HOST'])).'-'.$_SERVER['REQUEST_TIME']),
            $_POST['db_tablepre'],
            $_POST['db_driver'],
            $_POST['db_driver']===\Ddm\Db\PDO\Sqlite::class ? '' : $_POST['db_host'],
            $_POST['db_driver']===\Ddm\Db\PDO\Sqlite::class ? '' : $_POST['db_port'],
            $_POST['db_driver']===\Ddm\Db\PDO\Sqlite::class ? '' : $_POST['db_username'],
            $_POST['db_driver']===\Ddm\Db\PDO\Sqlite::class ? '' : $_POST['db_password'],
            $_POST['db_name'],
            $_POST['other_cache_driver'],
            $_POST['other_cache_prefix']
        ], file_get_contents(SITE_ROOT.'/install/define_template.xml.php'));
        Ddm::getHelper('core')->saveFile(SITE_ROOT.'/data/configs/define.xml.php', $xml);
        return $this;
    }

    /**
     * @return array
     */
    private function _checkPostData(): array
    {
        $result = ['status' => true, 'message' => ''];
        $setting = new Setting();
        foreach ($setting->getElements() as $group) {
            foreach ($group['elements'] as $name => $attributes) {
                $_POST[$name] = isset($_POST[$name]) ? trim($_POST[$name]) : '';
                if ($_POST['db_driver'] === \Ddm\Db\PDO\Sqlite::class) {
                    if ($name === 'db_host' || $name === 'db_port' || $name === 'db_username' || $name === 'db_password') {
                        continue;
                    }
                }
                if ($name != 'db_password' && $name != 'db_tablepre' && $_POST[$name] === '') {
                    $result['message'] = $attributes['label'] . '不能为空';
                } elseif ($_POST[$name] != '' && $name == 'db_tablepre' && !preg_match('/^\w+$/', $_POST[$name])) {
                    $result['message'] = '表名前缀必须是英文字母、数字和下划线或者是这些字符的组合';
                } elseif ($name == 'other_adminpath' && !preg_match('/^\w+$/', $_POST[$name])) {
                    $result['message'] = '后台登录入口会被用于访问后台的URL, 必须是英文字母、数字和下划线或者是这些字符的组合';
                } elseif ($name == 'other_cache_prefix' && !preg_match('/^\w+$/', $_POST[$name])) {
                    $result['message'] = '缓存键名前缀前缀必须是英文字母、数字和下划线或者是这些字符的组合';
                }
                if ($result['message'] != '') {
                    $result['status'] = false;
                    break;
                }
            }
        }
        if ($result['status']) {
            try {
                /** @var \Ddm\Db\InterfaceDb $driver */
                $driver = new $_POST['db_driver'](['host' => $_POST['db_host'], 'port' => $_POST['db_port'], 'username' => $_POST['db_username'], 'password' => $_POST['db_password'], 'dbname' => $_POST['db_name']]);
                $driver->close();
            } catch (\Exception $e) {
                $result['message'] = '无法连接数据库: ' . $e->getMessage();
                $result['status'] = false;
            }
        }
        return $result;
    }

    /**
     * @return $this
     */
    private function _savePostData(): static
    {
        Ddm::getHelper('core')->saveFile(SITE_ROOT . '/data/setting-post-data.tmp', serialize($_POST));
        return $this;
    }

    /**
     * @return $this
     */
    private function _createMainTable(): static
    {
        Db::schema()->createIfNotExists('modules', function (Table $table) {
            $table->string('module', 64)->nullable(false)->primary();
            $table->string('version', 16)->nullable(false);
            $table->tinyInteger('is_active')->nullable(false)->defaultValue(0);
            $table->engine('InnoDB');
        });
        return $this;
    }

    /**
     * @return $this
     */
    private function _saveAdminUser(): static
    {
        $admin = new Admin();
        $admin->getResource()->load($admin, $_POST['admin_username'], 'admin_name');
        if (!$admin->getId()) {
            $admin->load(1);
        }
        $admin->addData(['admin_name' => $_POST['admin_username'], 'admin_pass' => $_POST['admin_password'], 'groups_position' => [1 => 1], 'loginnum' => 0, 'is_active' => 1, 'edit_name' => 1, 'edit_pass' => 1])->save();
        return $this;
    }

    protected function _beforeRun(): static
    {
        $result = $this->_checkPostData();
        if ($result['status']) {
            $this->_saveConfig();
            if (file_exists(SITE_ROOT . '/data/setting-post-data.tmp')) {
                unlink(SITE_ROOT . '/data/setting-post-data.tmp');
            }
            Ddm::isInstalled(true);
            $this->clearCache();
            $this->_createMainTable();
            $install = new Install();
            $install->applyInstall();
            $this->_saveAdminUser();
        } else {
            $_POST['errors'] = $result['message'];
            $this->_savePostData();
            Request::redirect('./index.php?step=setting');
            $this->_template = null;
        }
        $this->addTitle('安装完成');
        return parent::_beforeRun();
    }

    protected function _afterRun(): static
    {
        parent::_afterRun();
        $this->clearCache();
        return $this;
    }
}
