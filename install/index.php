<?php

/**
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @author ddm
 * @copyright (c) 2010-2014 DDMCMS http://jinshui8.com/
 */
if (PHP_VERSION_ID < 80000) {
    echo '<div style="font:12px/1.35em arial, helvetica, sans-serif;">
<div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
<h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">
非常遗憾，你当前的PHP版本不能正常使用本系统</h3></div><p>DDMCMS需要运行在PHP 8.0或更高的版本</p></div>';
    exit;
}
$_SERVER['CMS_INSTALL'] = true;
require '../Ddm.php';
Ddm::init(0);
$configFile = SITE_ROOT . Config\Model\Config::XML_CONFIG_FILE;
if (is_file($configFile)) {
    Ddm\Request::singleton()->redirect('../', 301);
    exit;
}
$steps = ['welcome', 'check', 'setting', 'setup'];
$step = isset($_GET['step']) && in_array($_GET['step'] = strtolower($_GET['step']), $steps) ? ucfirst($_GET['step']) : 'Welcome';
$className = 'Step\\' . $step;
require SITE_ROOT . "/install/Step/AbstractModel.php";
require SITE_ROOT . "/install/Step/{$step}.php";
$install = new $className();
$install->setSteps($steps)->run();
