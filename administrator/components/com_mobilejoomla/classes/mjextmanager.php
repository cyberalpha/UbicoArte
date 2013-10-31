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

class MJExtManager
{
	static function changeState($table, $id, $markup)
	{
		$db = JFactory::getDBO();
		
		$query = "SELECT COUNT(*) FROM $table WHERE id=$id AND markup=".$db->Quote($markup);
		$db->setQuery($query);
		$unpublished = $db->loadResult();
		
		if($unpublished)
		{
			$query = "DELETE FROM $table WHERE id=$id AND markup=".$db->Quote($markup);
			$db->setQuery($query);
			$db->query();
			return true;
		}
		else
		{
			$query = "INSERT INTO $table (id, markup) VALUES ($id, ".$db->Quote($markup).")";
			$db->setQuery($query);
			$db->query();
			return false;
		}
	}
	
	static function getImage($published)
	{
		return $published
			? '<img src="components/com_mobilejoomla/images/publ-16.png" width="16" height="16" />'
			: '<img src="components/com_mobilejoomla/images/unpubl-16.png" width="16" height="16" />';
	}
}
