<?php
/**
 * Initialize extensions
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

require "inc.ClassExtensionMgr.php";
require_once "inc.ClassExtBase.php";

$extMgr = new SeedDMS_Extension_Mgr($settings->_rootDir."/ext", $settings->_cacheDir);
$extconffile = $extMgr->getExtensionsConfFile();
if(!file_exists($extconffile)) {
	$extMgr->createExtensionConf();
}
include($extconffile);

foreach($EXT_CONF as $extname=>$extconf) {
	if(!isset($extconf['disable']) || $extconf['disable'] == false) {
		$classfile = $settings->_rootDir."/ext/".$extname."/".$extconf['class']['file'];
		if(file_exists($classfile)) {
			include($classfile);
			$obj = new $extconf['class']['name'];
			if(method_exists($obj, 'init'))
				$obj->init();
		}
	}
}
