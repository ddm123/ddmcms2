<?php

namespace Core\Model;

use Config\Model\Config;
use Ddm;
use Ddm\Cache;
use Ddm\Db;
use Ddm\Db\InterfaceDb;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Install
{

    public const MODULES_CACHE_KEY = 'modules_version';

    public const INSTALL_LOCK_CACHE_KEY = 'modules_install_lock';

    protected mixed $_connection = null;

    protected mixed $_modulesVersion = null;

    /**
     * @return InterfaceDb
     */
    public function getConnection(): InterfaceDb
    {
        return $this->_connection === null ? $this->_connection = Db::getWriteConn() : $this->_connection;
    }

    /**
     * 执行一条SQL查询
     * @param string $sql
     * @return resource|bool
     */
    public function executeSql(string $sql): mixed
    {
        return $this->getConnection()->query($sql);
    }

    /**
     * @return array
     */
    public function getModulesVersion(): array
    {
        if ($this->_modulesVersion === null) {
            $this->_modulesVersion = Cache::load(self::MODULES_CACHE_KEY);
            if ($this->_modulesVersion === false) {
                $this->_modulesVersion = Db::table('modules')->pluck('version', 'module');
                Cache::save(self::MODULES_CACHE_KEY, $this->_modulesVersion, ['module'], 0);
            }
        }
        return $this->_modulesVersion;
    }

    /**
     * @param string $module
     * @param string $class
     * @return \Core\Model\AbstractModel
     */
    public function getModel(string $module, string $class): AbstractModel
    {
        $className = '\\' . ucfirst($module) . '\Model\\' . str_replace(' ', '\\', ucwords(str_replace('_', ' ', $class)));
        return new $className();
    }

    /**
     * @param array $data
     * @return $this
     */
    public function addAttribute(array $data): static
    {
        $tables = [];
        if (is_numeric(key($data))) {
            foreach ($data as $item) {
                if (Ddm::getHelper('core')->getEntityAttribute($item['entity_type'], $item['attribute_code'])) {
                    continue;
                }
                $a = new Attribute();
                $a->addData($item)->save();
                if ($a->attribute_id && $a->backend_type != 'static') {
                    $tables[$a->getTable()] = $a->getTable();
                }
            }
        } elseif (!Ddm::getHelper('core')->getEntityAttribute($data['entity_type'], $data['attribute_code'])) {
            $a = new Attribute();
            $a->addData($data)->save();
            if ($a->attribute_id && $a->backend_type != 'static') {
                $tables[$a->getTable()] = $a->getTable();
            }
        }
        $tables and $this->_createAttributeValueTables($tables);
        return $this;
    }

    /**
     * @param string $path
     * @param string|array $value
     * @param int $languageId
     * @return Config
     */
    public function addConfig(string $path, string|array $value, int $languageId = 0): Config
    {
        $config = $this->getModel('config', 'config');
        /** @var Config $config */
        return $config->loadFromPath($path)->addData(['path' => $path, 'values' => is_array($value) ? $value : [(int) $languageId => $value]])->save();
    }

    /**
     * @return $this
     */
    public function applyInstall(): static
    {
        if (!Cache::load(self::INSTALL_LOCK_CACHE_KEY)) {
            Cache::save(self::INSTALL_LOCK_CACHE_KEY, $_SERVER['REQUEST_TIME'], ['module'], 0);
            $modulesVersion = $this->getModulesVersion();
            $modules = Ddm::getModuleConfig();
            unset($modules['Core'], $modules['Language'], $modules['Config']);
            //调整下模块的安装顺序
            $modules = array_merge(['Config' => Ddm::getModuleConfig('Config'), 'Core' => Ddm::getModuleConfig('Core'), 'Language' => Ddm::getModuleConfig('Language')], $modules);
            foreach ($modules as $moduleName => $config) {
                if (empty($config['version'])) {
                    $config['version'] = '1.0.0.0';
                }
                $isActive = !empty($config['active']);
                if ($isActive) {
                    $this->_installModule($modulesVersion, $moduleName, $config);
                }
                if ($isActive || isset($modulesVersion[$moduleName])) {
                    $this->getConnection()->insert(Db::getTable('modules'), ['module' => $moduleName, 'version' => $isActive ? $config['version'] : $modulesVersion[$moduleName], 'is_active' => empty($config['active']) ? 0 : 1], false, ['version', 'is_active']);
                }
            }
        }
        return $this;
    }

    /**
     * @param array $modulesVersion
     * @param string $moduleName
     * @param array $config
     * @return $this
     */
    protected function _installModule(array $modulesVersion, string $moduleName, array $config): static
    {
        if (isset($modulesVersion[$moduleName])) {
            if ($files = $this->_getUpgradeFiles($moduleName, $config)) {
                $isUpgrade = false;
                foreach ($files as $file) {
                    if (preg_match('/sql\/upgrade-(.+?)\.php$/', $file, $v)) {
                        if (version_compare($v[1], $modulesVersion[$moduleName]) <= 0) {
                            continue;
                        }
                        if (version_compare($v[1], $config['version']) > 0) {
                            break;
                        }
                        include $file;
                    }
                }
            }
        } elseif ($files = $this->_getInstallFiles($moduleName, $config)) {
            foreach ($files as $file) {
                include $file;
            }
            if (preg_match('/sql\/install-(.+?)\.php$/', $file, $v)) {
                $modulesVersion[$moduleName] = $v[1];
                $this->_installModule($modulesVersion, $moduleName, $config);
            }
        }
        return $this;
    }

    /**
     * @param array|string $table
     * @return $this
     */
    protected function _createAttributeValueTables(array|string $table): static
    {
        foreach ((array) $table as $tableName) {
            $type = explode('_', $tableName);
            $this->_createAttributeValueTable($tableName, end($type));
        }
        return $this;
    }

    /**
     * @param string $tableName
     * @param string $type
     * @return bool
     */
    protected function _createAttributeValueTable(string $tableName, string $type): bool
    {
        if ($type === 'int') {
            $valueColumn = function (mixed $table) {
                $table->integer('value')->unsigned()->nullable(true);
            };
        } elseif ($type === 'varchar') {
            $valueColumn = function (mixed $table) {
                $table->string('value')->nullable(true);
            };
        } elseif ($type === 'text') {
            $valueColumn = function (mixed $table) {
                $table->text('value')->nullable(true);
            };
        } else {
            return false;
        }
        $tablePrefix = Db::getTable('');
        if ($tablePrefix) {
            $tableName = substr($tableName, strlen($tablePrefix));
        }
        Db::schema()->createIfNotExists($tableName, function (mixed $table) use ($tableName, $valueColumn) {
            $table->integer('entity_id')->unsigned()->nullable(false)->default(0);
            $table->integer('attribute_id')->unsigned()->nullable(false)->default(0);
            $table->integer('language_id')->unsigned()->nullable(false)->default(0);
            $valueColumn($table);
            $table->primary(['entity_id', 'attribute_id', 'language_id']);
            $table->index(['attribute_id'], $tableName . '_attribute_id');
            $table->engine('InnoDB');
        });
        return true;
    }

    /**
     * @param string $moduleName
     * @param array $config
     * @return array
     */
    private function _getInstallFiles(string $moduleName, array $config): array
    {
        if ($files = glob(SITE_ROOT . '/' . MODULES_FOLDER . '/' . $moduleName . '/sql/install-*.php')) {
            if (count($files) > 1) {
                usort($files, [$this, '_sortFilesCallback']);
            }
        }
        return $files;
    }

    /**
     * @param string $moduleName
     * @param array $config
     * @return array
     */
    private function _getUpgradeFiles(string $moduleName, array $config): array
    {
        if ($files = glob(SITE_ROOT . '/' . MODULES_FOLDER . '/' . $moduleName . '/sql/upgrade-*.php')) {
            if (count($files) > 1) {
                usort($files, [$this, '_sortFilesCallback']);
            }
        }
        return $files;
    }

    /**
     * @param string $a
     * @param string $b
     * @return int
     */
    private function _sortFilesCallback(string $a, string $b): int
    {
        preg_match('/sql\/(?:install|upgrade)-(.+?)\.php$/', $a, $v1);
        preg_match('/sql\/(?:install|upgrade)-(.+?)\.php$/', $b, $v2);
        return $v1 && $v2 ? version_compare($v1[1], $v2[1]) : 0;
    }
}
