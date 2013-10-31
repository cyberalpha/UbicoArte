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
// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.database.database');

/**
 * Indexer class.
 *
 * @package     Extly.Plugins
 * @subpackage  prsobiproplus
 * @since       1.0
 */
class PlgSearchPrSobiProPlusIndexer
{

	private $_scheduled;

	private $_catalog_list;

	private $_debug;

	private $_cron_running;

	/**
	 * Constructor.
	 * 
	 * @param   boolean  $scheduled     Scheduled flag
	 * @param   string   $catalog_list  List of sections
	 * @param   boolean  $debug         Debug flag
	 * @param   boolean  $cron_running  Cron running flag
	 *
	 * @since	1.5
	 */
	public function __construct($scheduled = 0, $catalog_list = null, $debug = 0, $cron_running = 0)
	{
		$this->_scheduled = $scheduled;
		$this->_catalog_list = $catalog_list;
		$this->_debug = $debug;
		$this->_cron_running = $cron_running;

		// If scheduled mode and not running under cron, return
		if (($this->_scheduled) && (!$this->_cron_running))
		{
			return;
		}

		$_db = JFactory::getDbo();
		$query = 'SELECT id FROM `#__prsobiproplus_tree` LIMIT 1;';
		$_db->setQuery($query);
		$_db->query();

		if ($_db->getErrorNum() != 0)
		{
			$this->_createTables();
			$this->checkLastRunRebuild();
		}
	}

	/**
	 * Check last run
	 *
	 * @return	nil.
	 * 
	 * @since	1.0
	 */
	public function checkLastRunRebuild()
	{
		// If scheduled mode and not running under cron, return
		if (($this->_scheduled) && (!$this->_cron_running))
		{
			return;
		}

		if ($this->_checkLastRun())
		{
			$catalog_list = $this->_catalog_list;
			if (!empty($catalog_list))
			{
				$catalogs = explode(',', $catalog_list);
			}
			else
			{
				$catalogs = plgSearchPrSobiProPlus::_getCatalogs();
			}

			if (!empty($catalogs))
			{
				$this->_clean_tree();
				foreach ($catalogs as $catalog_pid)
				{
					$this->_rebuild_tree($catalog_pid, 0, 1, null, $catalog_pid);
				}
			}
		}
	}

	/**
	 * Check last run internal
	 *
	 * @return	nil.
	 * 
	 * @since	1.0
	 */
	private function _checkLastRun()
	{
		// Get a reference to the global cache object.
		$config = & JFactory::getConfig();
		$cache_enabled = $config->getValue('config.caching');

		if ($this->_debug)
		{
			echo "</br>DEBUG+: Cache {$cache_enabled}</br>\n";
		}

		if (!$cache_enabled)
		{
			return true;
		}

		$_db = JFactory::getDbo();
		$query = "SELECT last_update FROM " . $_db->nameQuote('#__prsobiproplus_tree') . " LIMIT 1";
		$_db->setQuery($query);
		$last_run = $_db->loadResult();
		$last_run = strtotime($last_run);

		$interval = $config->getValue('cachetime');

		if ($this->_debug)
		{
			echo "</br>DEBUG+: Index Last Run " . $last_run;
			echo "</br>DEBUG+: Interval " . $interval;
		}

		return (time() > $last_run + $interval * 60);
	}

	/**
	 * Clean the tree
	 *
	 * @return	nil.
	 * 
	 * @since	1.0
	 */
	private function _clean_tree()
	{
		if ($this->_debug)
		{
			echo "</br>DEBUG+: Cleaning tree</br>\n";
		}

		$_db = JFactory::getDbo();
		$query = 'TRUNCATE TABLE `#__prsobiproplus_tree`;';
		$_db->setQuery($query);
		$_db->query();
	}

	/*
	 * Storing Hierarchical Data in a Database Article
	 * Modified Preorder Tree Traversal
	 * Script that converts an adjacency list to a modified preorder tree traversal table
	 * Based on http://www.sitepoint.com/hierarchical-data-database/
	 */

	/**
	 * Rebuild_tree
	 * 
	 * @param   object  $node        Current node
	 * @param   object  $parent      Parent node
	 * @param   object  $left        Left node
	 * @param   string  $path        Path to the node
	 * @param   string  $catalog_id  Section id
	 *
	 * @return	id.
	 * 
	 * @since	1.0
	 */
	private function _rebuild_tree($node, $parent, $left, $path, $catalog_id)
	{
		if ($this->_debug)
		{
			echo "</br>DEBUG+: Rebuilding tree ({$node}, {$parent}, {$left}, {$path}, {$catalog_id})</br>\n";
		}

		$_db = JFactory::getDbo();

// The right value of this node is the left value + 1
		$right = $left + 1;

// Get all children of this node
		$query = 'SELECT a.id FROM #__sobipro_relations AS a WHERE a.pid=' . $_db->Quote($node);
		$_db->setQuery($query);
		$rows = $_db->loadResultArray();
		foreach ($rows as $row)
		{
			/*
			 * Recursive execution of this function for each
			 * child of this node
			 * $right is the current right value, which is
			 * incremented by the _rebuild_tree function.
			 */

			$right = $this->_rebuild_tree($row, $node, $right, ($parent ? $path . $node . '-' : $node . '-'), $catalog_id);
		}

// We've got the left value, and now that we've processed
// the children of this node we also know the right value
		if ($parent)
		{
			$query = "INSERT INTO `#__prsobiproplus_tree` VALUES($node, $parent, $left, $right, " . $_db->Quote($path) . ", $catalog_id, now());";
			$_db->setQuery($query);
			$_db->query();
		}

// Return the right value of this node + 1
		return $right + 1;
	}

	/**
	 * Create temp table
	 *
	 * @return	nil.
	 * 
	 * @since	1.0
	 */
	private function _createTables()
	{
		$_db = JFactory::getDbo();
		$query = 'DROP TABLE IF EXISTS `#__prsobiproplus_tree`;';
		$_db->setQuery($query);
		$_db->query();

		$query = 'CREATE TABLE IF NOT EXISTS `#__prsobiproplus_tree` (
  `id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `path` varchar(1024) NOT NULL,
  `section` int(11) NOT NULL,  
  `last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (`id`),
  KEY `parent` (`parent`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `path` (`path`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		$_db->setQuery($query);
		$_db->query();
	}

}
