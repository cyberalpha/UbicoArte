<?php

/**
 * @package     Extly.Plugins
 * @subpackage  prsobiproplus - SobiPro Search Plugin+ (Plus)
 * 
 * @author      Prieco S.A. <support@extly.com>
 * @copyright   Copyright (C) 2007 - 2012 Prieco, S.A. All rights reserved.
 * @license     http://http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL 
 * @link        http://www.extly.com http://support.extly.com
 */
header("HTTP/1.0 200 OK");

global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_lang, $database, $mosConfig_mailfrom, $mosConfig_fromname;

/* * * access Joomla's configuration file ** */
$my_path = dirname(__FILE__);

if (file_exists($my_path . "/../../../configuration.php"))
{
	$absolute_path = dirname($my_path . "/../../../configuration.php");
	require_once $my_path . "/../../../configuration.php";
}
elseif (file_exists($my_path . "/../../configuration.php"))
{
	$absolute_path = dirname($my_path . "/../../configuration.php");
	require_once $my_path . "/../../configuration.php";
}
elseif (file_exists($my_path . "/configuration.php"))
{
	$absolute_path = dirname($my_path . "/configuration.php");
	require_once $my_path . "/configuration.php";
}
else
{
	die("Joomla Configuration File not found!");
}

$absolute_path = realpath($absolute_path);

// Set up the appropriate CMS framework
if (class_exists('jconfig'))
{
	define('_JEXEC', 1);
	define('JPATH_BASE', $absolute_path);
	define('DS', DIRECTORY_SEPARATOR);

// Load the framework
	require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
	require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

// Create the mainframe object
	$mainframe = & JFactory::getApplication('site');

// Initialize the framework
	$mainframe->initialise();

// Load system plugin group
	JPluginHelper::importPlugin('system');

// Trigger the onBeforeStart events
	$mainframe->triggerEvent('onBeforeStart');
	$lang = & JFactory::getLanguage();

	$mosConfig_absolute_path = JPATH_BASE;
}
else
{
	define('_VALID_MOS', '1');
	require_once $mosConfig_absolute_path . '/includes/joomla.php';
	require_once $mosConfig_absolute_path . '/includes/database.php';
	$database = new database($mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db, $mosConfig_dbprefix);
}

// Load Joomla Language File
if (file_exists($mosConfig_absolute_path . '/language/' . $mosConfig_lang . '.php'))
{
	require_once $mosConfig_absolute_path . '/language/' . $mosConfig_lang . '.php';
}
elseif (file_exists($mosConfig_absolute_path . '/language/english.php'))
{
	require_once $mosConfig_absolute_path . '/language/english.php';
}
/* * * END of Joomla config ** */

$version = new JVersion;
$joomla = $version->getShortVersion();
$is_j15 = (substr($joomla, 0, 3) == '1.5');
if ($is_j15)
{
	echo "prsobiproplus - SobiPro Search Plugin+ (Plus) \n<br/>";
	echo "===> NOT COMPATIBLE WITH JOOMLA 1.5 - Please, install an older version \n<br/>";
	die(1);
}

require_once JPATH_BASE . '/plugins/search/prsobiproplus/prsobiproplus.php';
require_once JPATH_BASE . '/plugins/search/prsobiproplus/prsobiproplus_indexer.php';

echo "# prsobiproplus - SobiPro Search Plugin+ (Plus)</br>\n";
echo "# ------------------------------------------------------------------------</br>\n";
echo "# author    Prieco S.A.</br>\n";
echo "# copyright Copyright (C) 2010 Prieco.com. All Rights Reserved.</br>\n";
echo "# @license - http://http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL</br>\n";
echo "# Websites: http://www.prieco.com</br>\n";
echo "# Technical Support:  Forum - http://www.prieco.com/en/forum/index.html</br></br>\n";

$plugin = & JPluginHelper::getPlugin('search', 'prsobiproplus');
$className = 'plgSearch' . $plugin->name;
if (class_exists($className))
{
	echo "  Job - Initialise</br>\n";
	$now = date('r');
	echo "  Job - Start {$now}</br>\n";

	$dispatcher = & JDispatcher::getInstance();
	$plugin = new $className($dispatcher, (array) $plugin);
	if ($plugin)
	{
		$scheduled_indexer = $plugin->params->def('scheduled_indexer', 0);
		$catalog_list = $plugin->params->def('catalog_list', null);
		$debugq = $plugin->params->def('debug', 0);
		$cronjob = true;

		echo "  Job - Parameters scheduled:$scheduled_indexer, catalog_list: $catalog_list, debug: $debugq, cronjob: $cronjob</br>\n";

		$job = new plgSearchPrSobiProPlusIndexer($scheduled_indexer, $catalog_list, $debugq, $cronjob);

		// Just in case
		$job->checkLastRunRebuild();

		echo "  Job - End {$now}</br>\n";
	}
	else
	{
		echo $className . " not instantiated!";
	}
}
else
{
	echo $className . " not found!";
}
