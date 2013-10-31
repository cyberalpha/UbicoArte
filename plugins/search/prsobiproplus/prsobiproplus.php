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

require_once 'prsobiproplus_indexer.php';

/**
 * SobiPro Search Plugin+ (Plus) class.
 *
 * @package     Extly.Plugins
 * @subpackage  prsobiproplus
 * @since       1.0
 */
class PlgSearchPrSobiProPlus extends JPlugin
{

	protected $scheduled_indexer = null;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 * 
	 * @since   1.0
	 * 
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		$version = new JVersion;
		$joomla = $version->getShortVersion();
		$is_j15 = (substr($joomla, 0, 3) == '1.5');
		if ($is_j15)
		{
			echo "prsobiproplus - SobiPro Search Plugin+ (Plus) \n<br/>";
			echo "===> NOT COMPATIBLE WITH JOOMLA 1.5 - Please, install an older version \n<br/>";
			die(1);
		}

		$catalog_list = $this->params->def('catalog_list', null);
		$scheduled_indexer = $this->params->def('scheduled_indexer', 0);
		$debugq = $this->params->def('debug', 0);
		
		if ($catalog_list)
		{
			$catalog_list = implode(',', $catalog_list);
		}		
		
		$this->_scheduled_indexer = new plgSearchPrSobiProPlusIndexer($scheduled_indexer, $catalog_list, $debugq);
	}

	/**
	 * J1.5 - onSearchAreas event
	 *
	 * @return	array An array of search areas.
	 * 
	 * @since	1.0
	 */
	public function onSearchAreas()
	{
		$title = $this->params->def('title', JText::_('PLG_SEARCH_PRSOBIPROPLUS_CONTACTS'));

		$areas = array();
		$areas['prsobiproplus'] = $title;
		return $areas;
	}

	/**
	 * onContentSearchAreas event
	 *
	 * @return	array An array of search areas.
	 * 
	 * @since	1.0
	 */
	public function onContentSearchAreas()
	{
		$title = $this->params->def('title', JText::_('PLG_SEARCH_PRSOBIPROPLUS_CONTACTS'));

		$areas = array();
		$areas['prsobiproplus'] = $title;

		return $areas;
	}

	/**
	 * J15 - onSearch event
	 *
	 * @param   string  $text      Text to search
	 * @param   string  $phrase    How to search
	 * @param   string  $ordering  How to order
	 * @param   string  $areas     Where to search
	 * 
	 * @return	array Result - The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 * 
	 * @since	1.0
	 */
	public function onSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		return $this->onContentSearch($text, $phrase, $ordering, $areas);
	}

	/**
	 * onContentSearch event
	 *
	 * @param   string  $text      Text to search
	 * @param   string  $phrase    How to search
	 * @param   string  $ordering  How to order
	 * @param   string  $areas     Where to search
	 * 
	 * @return	array Result - The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 * 
	 * @since	1.0
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$this->_scheduled_indexer->checkLastRunRebuild();

		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		// $groups = implode(',', $user->getAuthorisedViewLevels());
		$rows = array();

		if (is_array($areas))
		{
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
			{
				return $rows;
			}
		}

		$limit = $this->params->def('search_limit', 50);
		$search_field_list = $this->params->def('search_field_list', null);
		$search_username = $this->params->def('search_username', 0);
		$search_name = $this->params->def('search_name', 0);
		$catalog_list = $this->params->def('catalog_list', null);
		$custom_field_names = $this->params->def('custom_field_names', null);
		$search_category_list = $this->params->def('search_category_list', null);
		$desc_list = $this->params->def('desc_list', null);
		$menu_itemid = $this->params->def('menu_itemid', null);

		// $interval = $this->params->def('interval', 15);
		$ft_mode = $this->params->def('ft_mode', 0);
		$ft_mode_qe = $this->params->def('ft_mode_qe', 0);
		$sql_big_selects = $this->params->def('sql_big_selects', 0);
		$order_query = $this->params->def('order_query', null);
		$debugq = $this->params->def('debug', 0);
		$randomize = $this->params->def('randomize', 0);
		$allow_empty = $this->params->def('allow_empty', 0);
		$allow_guest_entries = $this->params->def('allow_guest_entries', 0);
		$only_approved = $this->params->def('only_approved', 0);

		if ($catalog_list)
		{
			$catalog_list = implode(',', $catalog_list);
		}
		if ($custom_field_names)
		{
			$custom_field_names = implode(',', $custom_field_names);
		}
		if ($search_category_list)
		{
			$search_category_list = implode(',', $search_category_list);
		}
		if ($search_field_list)
		{
			$search_field_list = implode(',', $search_field_list);
		}
		if ($desc_list)
		{
			$desc_list = implode(',', $desc_list);
		}

		$_db = JFactory::getDbo();

// SEARCH TEXT
		$text = trim($text);
		if ($text == '')
		{
			if ($debugq)
			{
				echo '</br>DEBUG+: No text';
			}
			return $rows;
		}

// CATEGORIES
		$sid_list = JRequest::getVar('sid_list', null);
		$category_path = $this->_build_category_path($_db, $search_category_list, $sid_list);

		$allow_empty = (($allow_empty) && (strlen($sid_list) > 0));
		$empty_search = ((strcmp($text, '----------') == 0) && ($allow_empty));
		if ($empty_search)
		{
			$text = '';
		}

// EMPTY SEARCH MODE
		if (($debugq) && ($empty_search))
		{
			echo '</br>DEBUG+: EMPTY SEARCH MODE';
		}

// MENU ITEM ID PARAM
		$menu_itemid = trim($menu_itemid);
		if (is_numeric($menu_itemid))
		{
			if ($debugq)
			{
				echo "</br>DEBUG+: Menu_itemid=$menu_itemid";
			}
			$menu_itemid = '&Itemid=' . $menu_itemid;
		}
		else
		{
			if ($debugq)
			{
				echo '</br>DEBUG+: No menu_itemid';
			}
			$menu_itemid = '';
		}

		$section = $this->params->def('title', JText::_('PLG_SEARCH_PRSOBIPROPLUS_CONTACTS'));

// SECTIONS
		if (!empty($catalog_list))
		{
			$catalogs = explode(',', $catalog_list);
			if ($debugq)
			{
				echo "</br>DEBUG+: Catalogs=" . implode('-', $catalogs);
			}
		}
		else
		{
			$catalogs = $this->_getCatalogs();
			if ($debugq)
			{
				echo '</br>DEBUG+: All catalogs.' . implode('-', $catalogs);
			}
		}

// FIELD NAME FOR EACH SECTION
		$field_name = $this->_getFieldNames($catalogs);
		if (!empty($custom_field_names))
		{
			$field_name = $this->_replaceFieldNames($field_name, $custom_field_names);
		}

// DESCRIPTIONS
		if (!empty($desc_list))
		{
			$descs = explode(',', $desc_list);
			if ($debugq)
			{
				echo "</br>DEBUG+: Descs=" . implode('-', $descs) . ' n=' . count($descs);
			}
		}
		else
		{
			if ($debugq)
			{
				echo '</br>DEBUG+: No descs.';
			}
		}

// RANDOMIZE
		if ($randomize)
		{
			$order = 'RAND()';
		}

// BUILD QUERY
		$full_query = null;
		$i = 0;

// FOR EACH SECTION
		foreach ($catalogs as $catalog_pid)
		{
// FIELDS
			$fields = $this->_loadFields($catalog_pid, $search_field_list);
			if (empty($fields))
			{
				if ($debugq)
				{
					echo '</br>DEBUG+: No fields';
				}
				return $rows;
			}
// FIELDS TABLES
			$field_tables = $this->_genSqlFieldTables($fields);

			if (!$empty_search)
			{
				$where_text = $this->_genSqlWhereTextFields($phrase, $text, $fields);
			}

// IF NOT RANDOMIZE, SELECTED ORDER
			if (!$randomize)
			{
				$order = $this->_genSqlOrder($ordering);
				if (!empty($order_query))
				{
					$order = $order_query . ',' . $order;
				}
			}

// DESCRIPTION TABLE FIELD AND TABLE
			$desc_id = null;
			if ((!empty($desc_list)) && ($i < count($descs)))
			{
				$desc_id = $descs[$i];
			}

			if (is_numeric($desc_id))
			{
				if ($debugq)
				{
					echo "</br>DEBUG: Desc_id ($i) $desc_id";
				}
				$dsc_table = '#__sobipro_field_data AS dsc ON a.id = dsc.sid AND dsc.fid = ' . $_db->Quote($desc_id);
				$dsc_field = 'dsc.baseData AS text, ';
			}
			else
			{
				if ($debugq)
				{
					echo "</br>DEBUG: No desc_id ($i)";
				}
				$dsc_table = null;
				$dsc_field = 'CONCAT_WS(", ", n.baseData, c.name) AS text, ';
			}

// ALL ENTRIES UNDER THE SAME SECTION
			$catalog_path = 't.path LIKE ' . $_db->Quote($catalog_pid . '-%');

			$query = $_db->getQuery(true);
			$query->select('n.baseData AS title, a.updatedTime AS created, '
					. 'a.id as slug, '
					. 'c.id AS catslug, '
					. $dsc_field
					. 'CONCAT_WS(" / ", ' . $_db->Quote($section) . ', c.name) AS section, "2" AS browsernav,'
					. 'a.id id,'
					. $_db->Quote($catalog_pid) . ' catalog_pid');
			$query->from('#__sobipro_object AS a');

// SECTION
			$query->innerJoin('#__prsobiproplus_tree AS t ON a.id = t.id AND ' . $catalog_path);

// CATEGORY
			$query->innerJoin('#__sobipro_object AS c ON a.parent = c.id');

			if (($search_username) || ($search_name) || (!$allow_guest_entries))
			{
				$query->innerJoin('#__users AS u ON u.id = a.owner');
			}

// GET NAME FORM FIELD, NOT FROM OBJECT
			$query->innerJoin('#__sobipro_field_data AS n ON a.id = n.sid AND n.fid=' . $field_name[$catalog_pid]->sValue);

// CATEGORIES
			$query->leftJoin('#__sobipro_relations AS rc ON a.id = rc.id and rc.oType=' . $_db->Quote('entry'));

// CATEGORIES
			$query->leftJoin('#__sobipro_language AS lc ON rc.pid = lc.id and lc.sKey=' . $_db->Quote('name'));

// OPTIONS
			$query->leftJoin('#__sobipro_field_option_selected AS o ON a.id = o.sid');

// OPTIONS - Un-optimized due compatibility problem - see Jeroen
			$query->leftJoin('#__sobipro_language AS ol ON o.fid = ol.fid and ol.sKey=o.optValue AND ol.oType=' . $_db->Quote('field_option'));

			foreach ($field_tables as $field_table)
			{
// FIELD TABLES TO CREATE A FULL ENTRY
				$query->leftJoin($field_table);
			}

			if (!empty($dsc_table))
			{
				$query->leftJoin($dsc_table);
			}

			$whereq = 'a.oType=' . $_db->Quote('entry');
			if (!$empty_search)
			{
				$whereq = $whereq . ' AND ' . $where_text;
			}
			$whereq = $whereq . ' AND c.oType=' . $_db->Quote('category');
			$whereq = $whereq . ' AND lc.oType=' . $_db->Quote('category');

// $whereq = $whereq . ' AND ol.oType="field_option"'; - Un-optimized due compatibility problem - see Jeroen

			$whereq = $whereq . ' AND a.state=1 AND c.state=1 ';

			if ($only_approved)
			{
				$whereq = $whereq . ' AND a.approved=1 ';
			}

			if (!empty($category_path))
			{
				$whereq = $whereq . ' AND ' . $category_path;
			}
			$query->where($whereq);

			$query->group('a.id');

			if ($debugq)
			{
				echo "</br>DEBUG+: Sub-Query " . $query;
			}

			if (empty($full_query))
			{
				$full_query = '(' . $query . ')';
			}
			else
			{
				$full_query = $full_query . ' UNION (' . $query . ')';
			}
			$i++;
		}
		$full_query = $full_query . ' ORDER BY ' . $order;

		if ($sql_big_selects)
		{
			if ($debugq)
			{
				echo "</br>DEBUG+: SET OPTION SQL_BIG_SELECTS=1";
			}
			$_db->setQuery("SET OPTION SQL_BIG_SELECTS=1");
			$_db->query();
		}

		$_db->setQuery($full_query, 0, $limit + 1);
		$rows = $_db->loadObjectList();

		if ($debugq)
		{
			echo "</br>DEBUG+: Query " . $full_query;
		}

		if (is_null($rows))
		{
			if ($ft_mode)
			{
				$msg = "</br></br><b>WARNING+: Full-text search enabled. 
					You may need to add the following indexes.</br></br>
					Please, manually execute the following MySQL queries to add the indexes:</br></br>";
				$msg .= "</br>ALTER TABLE " . $_db->getPrefix() . "sobipro_object ADD FULLTEXT(name);";
				$msg .= "</br>ALTER TABLE " . $_db->getPrefix() . "sobipro_language ADD FULLTEXT(sValue);";
				$msg .= "</br>ALTER TABLE " . $_db->getPrefix() . "sobipro_field_data ADD FULLTEXT(baseData);";
				$msg .= "</br>ALTER TABLE " . $_db->getPrefix() . "users ADD FULLTEXT(username);";
				$msg .= "</br>ALTER TABLE " . $_db->getPrefix() . "users ADD FULLTEXT(name);</b></br></br>";

				JError::raiseWarning(100, $msg);
			}

			echo "</br>ERROR+: " . nl2br($_db->getErrorMsg());
			return $rows;
		}

		if ($rows)
		{
			$lang = JRequest::get('lang');
			$lng = null;
			if (($lang) && (array_key_exists('lang', $lang)))
			{
				$lng = '&lang=' . $lang['lang'];
			}

			foreach ($rows as $key => $row)
			{
				$url = 'index.php?option=com_sobipro&pid=' .
						$rows[$key]->catalog_pid . '&sid=' .
						$row->slug . ':' . urlencode($rows[$key]->title) .
						'&catid=' . $row->catslug . $menu_itemid . $lng;
				$rows[$key]->href = JRoute::_($url);
			}
			if (count($rows) > $limit)
			{
				JError::raiseWarning(100, JText::_('PLG_SEARCH_PRSOBIPROPLUS_HITLIMIT'));
			}
			if ($debugq)
			{
				echo "</br>DEBUG+: Results: " . count($rows);
			}
		}
		else
		{
			if ($debugq)
			{
				echo "</br>DEBUG+: No results";
			}
		}

		return $rows;
	}

	/**
	 * Replace Field Names
	 * 
	 * @param   array   $field_names         Field names
	 * @param   string  $custom_field_names  List of custom field names
	 *
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _replaceFieldNames($field_names, $custom_field_names)
	{
		$new_field_names = $field_names;
		$custom_field_names = explode(',', $custom_field_names);

		$n = count($custom_field_names);
		$i = 0;
		foreach ($field_names as $section => $value)
		{
			if ($i == $n)
			{
				break;
			}

			$f = new stdClass;
			$f->section = $section;
			$f->sValue = $custom_field_names[$i];

			$new_field_names[$section] = $f;
			$i++;
		}
		return $new_field_names;
	}

	/**
	 * Load Fields
	 * 
	 * @param   string  $catalog_pid        Section
	 * @param   string  $search_field_list  List of fields
	 *
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _loadFields($catalog_pid, $search_field_list)
	{
		$debugq = $this->params->def('debug', 0);
		$_db = JFactory::getDbo();

		$search_field_arr = explode(',', $search_field_list);
		$query_fields_search = '';
		if ((!empty($search_field_list)) && (count($search_field_arr) > 0))
		{
			$query_fields_search = " AND fid IN (" . implode(",", $search_field_arr) . ") ";
		}

// Load text fields
		$query = $_db->getQuery(true);
		$query->select('fid');
		$query->from('#__sobipro_field AS f');
		$query->where(
				'f.section=' . $_db->Quote($catalog_pid) .
				' AND ( f.fieldType=' . $_db->Quote('inbox') .
				' OR f.fieldType=' . $_db->Quote('select') .
				' OR f.fieldType=' . $_db->Quote('textarea') . ')' .
				$query_fields_search
		);
		$query->order('fid');

		if ($debugq)
		{
			echo "</br>DEBUG+: Fields Query " . $query;
		}

		$_db->setQuery($query);
		$fields = $_db->loadObjectList();

		return $fields;
	}

	/**
	 * Generate SQL for fields
	 * 
	 * @param   array  $fields  List of fields
	 *
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _genSqlFieldTables($fields)
	{
		$_db = JFactory::getDbo();
		$field_tables = array();
		$i = 1;
		foreach ($fields as $field)
		{
			$field_tables[] = '#__sobipro_field_data AS f' . $i . ' ON a.id = f' . $i . '.sid AND f' . $i . '.fid = ' . $_db->Quote($field->fid);
			$i++;
		}
		return $field_tables;
	}

	/**
	 * Generate SQL for fields
	 * 
	 * @param   string  $phrase  param
	 * @param   string  $text    param
	 * @param   string  $fields  list of fields
	 *
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _genSqlWhereTextFields($phrase, $text, $fields)
	{
		$ft_mode = $this->params->def('ft_mode', 0);
		$ft_mode_qe = $this->params->def('ft_mode_qe', 0);
		$search_username = $this->params->def('search_username', 0);
		$search_name = $this->params->def('search_name', 0);

		if ($ft_mode_qe)
		{
			$qe_query = 'WITH QUERY EXPANSION';
		}
		else
		{
			$qe_query = '';
		}

		$_db = JFactory::getDbo();
		$where_text = '';

		// FT
		if ($ft_mode == 1)
		{
			$phrase = 'exact';
		}

		$wheres = array();
		switch ($phrase)
		{
			case 'exact':
				$wheres2 = array();

				if ($ft_mode == 0)
				{ // LIKE
					$text = $_db->Quote('%' . $_db->getEscaped($text, true) . '%', false);

					$wheres2[] = 'n.baseData LIKE ' . $text;
					$wheres2[] = 'a.name LIKE ' . $text;
					$wheres2[] = 'lc.sValue LIKE ' . $text;
					$wheres2[] = 'ol.sValue LIKE ' . $text;
					if ($search_username)
					{
						$wheres2[] = 'u.username LIKE ' . $text;
					}
					if ($search_name)
					{
						$wheres2[] = 'u.name LIKE ' . $text;
					}

					$i = 1;
					foreach ($fields as $field)
					{
						$wheres2[] = 'f' . $i . '.baseData LIKE ' . $text;
						$i++;
					}
				}
				else
				{ // FT
					$text = $_db->Quote($_db->getEscaped($text, true), false);

					$wheres2[] = "MATCH (n.baseData) AGAINST ($text $qe_query)";
					$wheres2[] = "MATCH (a.name) AGAINST ($text $qe_query)";
					$wheres2[] = "MATCH (lc.sValue) AGAINST ($text $qe_query)";
					$wheres2[] = "MATCH (ol.sValue) AGAINST ($text $qe_query)";
					if ($search_username)
					{
						$wheres2[] = "MATCH (u.username) AGAINST ($text $qe_query)";
					}
					if ($search_name)
					{
						$wheres2[] = "MATCH (u.name) AGAINST ($text $qe_query)";
					}

					$i = 1;
					foreach ($fields as $field)
					{
						$wheres2[] = "MATCH (f" . $i . ".baseData) AGAINST ($text $qe_query)";
						$i++;
					}
				}

				$where_text = '(' . implode(') OR (', $wheres2) . ')';
				break;

			case 'all':
			case 'any':
			default:
				$words = explode(' ', $text);
				$wheres = array();

				foreach ($words as $word)
				{
					$wheres2 = array();

					if ($ft_mode == 0)
					{ // LIKE
						$word = $_db->Quote('%' . $_db->getEscaped($word, true) . '%', false);

						$wheres2[] = 'n.baseData LIKE ' . $word;
						$wheres2[] = 'a.name LIKE ' . $word;
						$wheres2[] = 'lc.sValue LIKE ' . $word;
						$wheres2[] = 'ol.sValue LIKE ' . $word;
						if ($search_username)
						{
							$wheres2[] = 'u.username LIKE ' . $word;
						}
						if ($search_name)
						{
							$wheres2[] = 'u.name LIKE ' . $word;
						}

						$i = 1;
						foreach ($fields as $field)
						{
							$wheres2[] = 'f' . $i . '.baseData LIKE ' . $word;
							$i++;
						}
					}
					else
					{ // FT
						$word = $_db->Quote($_db->getEscaped($word, true), false);

						$wheres2[] = "MATCH (n.baseData) AGAINST ($word $qe_query)";
						$wheres2[] = "MATCH (a.name) AGAINST ($word $qe_query)";
						$wheres2[] = "MATCH (lc.sValue) AGAINST ($word $qe_query)";
						$wheres2[] = "MATCH (ol.sValue) AGAINST ($word $qe_query)";
						if ($search_username)
						{
							$wheres2[] = "MATCH (u.username) AGAINST ($word $qe_query)";
						}
						if ($search_name)
						{
							$wheres2[] = "MATCH (u.name) AGAINST ($word $qe_query)";
						}

						$i = 1;
						foreach ($fields as $field)
						{
							$wheres2[] = "MATCH (f" . $i . ".baseData) AGAINST ($word $qe_query)";
							$i++;
						}
					}

					$wheres[] = implode(' OR ', $wheres2);
				}

				$where_text = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
				break;
		}
		return $where_text;
	}

	/**
	 * Generate SQL for order
	 * 
	 * @param   string  $ordering  param
	 *
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _genSqlOrder($ordering)
	{
		switch ($ordering)
		{
			case 'alpha':
				$order = 'title ASC';
				break;

			case 'category':
				$order = 'section ASC, title ASC';
				break;

			case 'popular':
			case 'newest':
			case 'oldest':
			default:
				$order = 'title DESC';
		}
		return $order;
	}

	/**
	 * Return sections
	 * 
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	public static function _getCatalogs()
	{
		$_db = JFactory::getDbo();
		$query = 'SELECT id FROM `#__sobipro_object` WHERE parent=0 AND state=1 ORDER BY id';
		$_db->setQuery($query);
		return $_db->loadResultArray();
	}

	/**
	 * cleanListOfNumerics
	 * 
	 * @param   string  $listOfNumerics  param
	 * 
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _cleanListOfNumerics($listOfNumerics)
	{
		return preg_replace('/[^,0-9]/', '', $listOfNumerics);
	}

	/**
	 * _build_category_path
	 * 
	 * @param   object  &$_db                  param
	 * @param   string  $search_category_list  param
	 * @param   string  $sid_list              param
	 * 
	 * @return	string.
	 * 
	 * @since	1.0
	 */
	private function _build_category_path(&$_db, $search_category_list, $sid_list)
	{
		if ($search_category_list)
		{
			$search_category_list = explode(',', $search_category_list);
		}

		if ($sid_list)
		{
			$sid_list = $this->_cleanListOfNumerics($sid_list);
			if ($sid_list)
			{
				$sid_list = explode(',', $sid_list);
			}
		}

		if (is_array($sid_list))
		{
			$category_list = $sid_list;
		}
		else
		{
			if (is_array($search_category_list))
			{
				$category_list = $search_category_list;
			}
		}

		$category_path = null;
		if ((($search_category_list) || ($sid_list)) && (count($category_list) > 0))
		{
// T.path LIKE '%-999-%' OR t.path LIKE '%-888-%'
			$category_path = '';
			for ($i = 0; $i < count($category_list); $i++)
			{
				$category = $category_list[$i];

				if ($i > 0)
				{
					$category_path = $category_path . ' OR ';
				}
				$category_path = $category_path . 't.path LIKE ' . $_db->Quote('%-' . $category . '-%');
			}
			$category_path = '(' . $category_path . ')';
		}
		return $category_path;
	}

	/**
	 * _getFieldNames
	 * 
	 * @param   array  $catalogs  param
	 * 
	 * @return	array.
	 * 
	 * @since	1.0
	 */
	private function _getFieldNames($catalogs)
	{
		$catalogs_list = implode(',', $catalogs);
		$_db = JFactory::getDbo();
		$query = 'SELECT cfg.section, cfg.sValue FROM `#__sobipro_config` cfg 
			WHERE cfg.section IN (' . $catalogs_list . ') 
				AND cfg.sKey=' . $_db->Quote('name_field');
		$_db->setQuery($query);
		$results = $_db->loadObjectList('section');

		return $results;
	}

	/**
	 * _checkLastRunRebuild
	 * 
	 * @return	boolean.
	 * 
	 * @since	1.0
	 */
	public function _checkLastRunRebuild()
	{
		$this->_scheduled_indexer->checkLastRunRebuild();
	}

}
