<?php
/**
 * Mobile Joomla!
 * http://www.mobilejoomla.com
 *
 * @version		1.2.3
 * @license		GNU/GPL v2 - http://www.gnu.org/licenses/gpl-2.0.html
 * @copyright	(C) 2008-2013 Kuneri Ltd.
 * @date		January 2013
 */
defined('_JEXEC') or die('Restricted access');

include_once dirname(__FILE__).'/classes/mjinstaller.php';

function com_install()
{
	return MjInstaller::install();
}

function com_uninstall()
{
	return MjInstaller::uninstall();
}
