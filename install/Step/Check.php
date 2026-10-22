<?php

namespace Step;

use Ddm\Request;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
class Check extends AbstractModel
{

    protected ?string $_template = 'check.phtml';

    protected ?array $_checkResult = null;

    /**
     * @return $this
     */
    public function check(): static
    {
        if ($this->_checkResult === null) {
            $this->_checkResult = [];
            if (!extension_loaded('pdo_mysql') && !extension_loaded('pdo_sqlite')) {
                $this->_checkResult[] = '你需要安装PDO数据库扩展';
            }
            if (!extension_loaded('xml')) {
                $this->_checkResult[] = '你需要安装XML扩展库, 应用于配置文件解析';
            }
            if (!extension_loaded('gd')) {
                $this->_checkResult[] = '你需要安装GD扩展库, 应用于图像处理';
            }
            //if(!extension_loaded('mbstring')){
            //	$this->_checkResult[] = '你需要安装mbstring扩展库, 应用于处理中文字符';
            //}
            if (!extension_loaded('curl')) {
                $this->_checkResult[] = '你需要安装CURL扩展库';
            }
            if (!is_dir(SITE_ROOT . '/data')) {
                if (false === @mkdir(SITE_ROOT . '/data', 0777)) {
                    $this->_checkResult[] = '请在' . SITE_ROOT . '/目录下创建一个名为data的文件夹, 并设置有写入的权限';
                }
            } elseif (!file_put_contents(SITE_ROOT . '/data/~testCreateFile.txt', 'Please delete this file', LOCK_EX)) {
                $this->_checkResult[] = SITE_ROOT . '/data 文件夹需要有写入的权限';
            } elseif (!unlink(SITE_ROOT . '/data/~testCreateFile.txt')) {
                $this->_checkResult[] = SITE_ROOT . '/data 文件夹需要有删除文件的权限';
            }
        }
        return $this;
    }

    /**
     * @return null|string[]
     */
    public function getCheckResult(): ?array
    {
        return $this->_checkResult;
    }

    /**
     * @return string|false
     */
    public function getPHPVersion(): string|false
    {
        return phpversion();
    }

    protected function _beforeRun(): static
    {
        $this->clearCache();
        $this->check();
        if (empty($this->_checkResult)) {
            Request::redirect('./index.php?step=setting');
            $this->_template = null;
        } else {
            $this->addTitle('检测您的PHP运行环境');
        }
        return parent::_beforeRun();
    }
}
