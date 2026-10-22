<?php

namespace Step;

use Ddm;
use Ddm\Cache;
use Ddm\Cache\File;
use Ddm\Cache\Sqlite;
use Ddm\Db;

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
abstract class AbstractModel
{

    protected array $_steps = [];

    protected string $_title = '安装界面';

    protected ?string $_template = null;

    protected string $_headerTemplate = 'header.phtml';

    protected string $_footerTemplate = 'footer.phtml';

    /**
     * @param string $title
     * @return $this
     */
    public function addTitle(string $title): static
    {
        $this->_title = "{$title} - {$this->_title}";
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->_title;
    }

    /**
     * @param array $steps
     * @return $this
     */
    public function setSteps(array $steps): static
    {
        $this->_steps = $steps;
        return $this;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate(string $template): static
    {
        $this->_template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->_template;
    }

    /**
     * @return $this
     */
    public function clearCache(): static
    {
        $cacheDevice = Cache::singleton()->getCache();
        /** @var File|Sqlite $cacheDevice */
        if ($cacheDevice instanceof File) {
            if (!is_dir($cacheDevice->getCacheDir())) {
                return $this;
            }
        } elseif ($cacheDevice instanceof Sqlite) {
            if (!file_exists($cacheDevice->getCacheDir())) {
                return $this;
            }
        }
        Ddm::getConfig()->clearCache();
        Cache::clear();
        Db::clearCache();
        return $this;
    }

    /**
     * @return $this
     */
    public function run(): static
    {
        try {
            $this->_beforeRun();
            if ($this->getTemplate()) {
                include SITE_ROOT . '/design/install/template/' . $this->_headerTemplate;
                include SITE_ROOT . '/design/install/template/' . $this->getTemplate();
                include SITE_ROOT . '/design/install/template/' . $this->_footerTemplate;
            }
            $this->_afterRun();
        } catch (\Exception $e) {
            echo '<pre>' . $e->getMessage() . '<br/>' . $e->getTraceAsString() . '</pre>';
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeRun(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterRun(): static
    {
        return $this;
    }
}
