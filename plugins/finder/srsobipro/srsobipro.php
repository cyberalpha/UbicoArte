<?php

/* ------------------------------------------------------------------------
  # srsobipro - Search Reach for SobiPro
  # ------------------------------------------------------------------------
  # author    Prieco S.A.
  # copyright Copyright (C) 2012 Prieco.com. All Rights Reserved.
  # @license - http://http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
  # Websites: http://www.prieco.com
  # Technical Support:  Forum - http://www.prieco.com/en/forum/index.html
  ------------------------------------------------------------------------- */

defined('JPATH_BASE') or die;

jimport('joomla.application.component.helper');

// Load the base adapter.
require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';
require_once JPATH_ROOT . '/components/com_sobipro/lib/sobi.php';

/**
 * Finder adapter for SobiPro.
 *
 * @subpackage  Finder.Srsobipro 
 */
class plgFinderSrsobipro extends FinderIndexerAdapter {

    /**
     * The plugin identifier.
     * @var    string
     */
    protected $context = 'Srsobipro';

    /**
     * The extension name.
     * @var    string
     */
    protected $extension = 'com_sobipro';

    /**
     * The sublayout to use when rendering the results.
     * @var    string
     */
    protected $layout = 'srspentry';

    /**
     * The type of srsobipro that the adapter indexes.
     * @var    string
     */
    protected $type_title = 'Entry';

    /**
     * The table name.
     * @var    string
     */
    protected $table = '#__sobipro_object';

    /**
     * All fields.
     * @var    array
     */
    protected $all_fields;

    /**
     * The menu itemid.
     * @var    string
     */
    protected $menu_itemid;

    /**
     * Constructor
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An array that holds the plugin configuration
     */
    public function __construct(&$subject, $config) {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        $this->all_fields = array();
        Sobi::Init(JPATH_ROOT, JFactory::getConfig()->getValue('config.language'));

        $_db = JFactory::getDbo();
        $query = 'SELECT id FROM `#__srsobipro_tree` LIMIT 1;';
        $_db->setQuery($query);
        $_db->query();

        if ($_db->getErrorNum() != 0) {
            $this->_createTables();
            $this->_checkLastRunRebuild();
        }
    }

    /**
     * Method to index an item. The item must be a FinderIndexerResult object.
     *
     * @param   FinderIndexerResult  $item    The item to index as an FinderIndexerResult object.
     * @param   string               $format  The item format
     *
     * @return  void
     *
     * @throws  Exception on database error.
     */
    protected function index(FinderIndexerResult $item, $format = 'html') { //ok
        // Check if the extension is enabled
        if (JComponentHelper::isEnabled($this->extension) == false) {
            return;
        }

        // Initialize the item parameters.
        $registry = new JRegistry;
        $registry->loadString($item->params);
        $item->params = $registry;

        // Build the necessary route and path information.
        $title = urlencode($item->title);
        $url = "index.php?option=com_sobipro&pid={$item->sid}&sid={$item->id}:{$title}&catid={$item->catid}{$this->menu_itemid}";

        $item->url = $url;
        $item->route = $url;
        $item->path = FinderIndexerHelper::getContentPath($item->route);

        foreach ($this->all_fields as $field) {
            $item->addInstruction(FinderIndexer::META_CONTEXT, $field->nid);
        }

        // Handle the contact user name.
        $item->addInstruction(FinderIndexer::META_CONTEXT, 'user');

        // Add the type taxonomy data.
        $item->addTaxonomy('Type', 'SobiPro');

        // Add the category taxonomy data.
        $item->addTaxonomy('Category', $item->category, $item->cat_state, $item->cat_access);

        // Get content extras.
        FinderIndexerHelper::getContentExtras($item);

        // Index the item.
        FinderIndexer::index($item);
    }

    /**
     * Method to setup the indexer to be run.
     *
     * @return  boolean  True on success.
     */
    protected function setup() { //ok
        return true;
    }

    /**
     * Method to get the SQL query used to retrieve the list of content items.
     *
     * @param   mixed  $sql  A JDatabaseQuery object or null.
     *
     * @return  JDatabaseQuery  A database object.
     */
    protected function getListQuery($sql = null) {
        $this->_checkLastRunRebuild();

        $_db = JFactory::getDbo();
        $app = JFactory::getApplication();

        $search_field_list = $this->params->def('search_field_list', null);
        $catalog_list = $this->params->def('catalog_list', null);
        $menu_itemid = $this->params->def('menu_itemid', null);
        $sql_big_selects = $this->params->def('sql_big_selects', 0);

        //$catalog_list = $this->_cleanListOfNumerics($catalog_list);
        $search_field_list = $this->_cleanListOfNumerics($search_field_list);

// MENU ITEM ID PARAM
        $menu_itemid = trim($menu_itemid);
        ////// JLog::add("Menu_itemid=$menu_itemid", JLog::INFO);
        if (is_numeric($menu_itemid))
            $this->menu_itemid = '&Itemid=' . $menu_itemid;

        if ($sql_big_selects) {
            ////// JLog::add("SET OPTION SQL_BIG_SELECTS=1", JLog::INFO);
            $_db->setQuery("SET OPTION SQL_BIG_SELECTS=1");
            $_db->query();
        }

// SECTIONS
        if (!empty($catalog_list)) {
            $catalogs = explode(',', $catalog_list);

            ////// JLog::add("Catalogs=" . implode('-', $catalogs), JLog::INFO);
        } else {
            $catalogs = $this->_getCatalogs();

            ////// JLog::add("All Catalogs=" . implode('-', $catalogs), JLog::INFO);
        }

// FIELD NAME FOR EACH SECTION
        $field_name = $this->_getFieldNames($catalogs);

// BUILD QUERY
        $full_query = null;
        $i = 0;

// FOR EACH SECTION
        $catalog_pid = $catalogs[0];
// FIELDS
        $fields = $this->_loadFields($catalog_pid, $search_field_list);
        if (empty($fields)) {
            ////// JLog::add("No fields", JLog::INFO);
            return $rows;
        }
        $this->all_fields = array_merge($this->all_fields, $fields);
// FIELDS TABLES
        $field_tables = $this->_genSqlFieldTables($fields);
        $field_selects = $this->_genSqlFieldSelect($fields);
// ALL ENTRIES UNDER THE SAME SECTION
        $catalog_path = 't.path LIKE ' . $_db->Quote($catalog_pid . '-%');

        $query = $_db->getQuery(true);

        /* ---------------------------------------------------------------------------- */
        $query->select($_db->Quote($catalog_pid) . ' AS sid');

        $query->select('a.id');
        $query->select('n.baseData AS title');
        $query->select('a.nid AS alias');

        $query->select('a.createdTime AS start_date');
        $query->select('ow.username AS created_by_alias');
        $query->select('ow.username AS user');
        $query->select('a.updatedTime AS modified');
        $query->select('u.username AS modified_by');

        $query->select('a.metaKeys AS metakey');
        $query->select('a.metaDesc AS metadesc');
        //$query->select('lc. AS language');

        $query->select('a.validSince AS publish_start_date');
        $query->select('a.validUntil AS publish_end_date');

        $query->select('1 AS access');
        $query->select('a.state AS state');
        $query->select('a.id AS ordering');
        $query->select('a.params AS params');

        $query->select('c.id AS catid');
        $query->select('c.name AS category');
        $query->select('c.state AS cat_state');
        $query->select('1 AS cat_access');

        // Handle the alias CASE WHEN portion of the query
        $case_when_item_alias = ' CASE WHEN ';
        $case_when_item_alias .= $query->charLength('a.nid');
        $case_when_item_alias .= ' THEN ';
        $a_id = $query->castAsChar('a.id');
        $case_when_item_alias .= $query->concatenate(array($a_id, 'a.nid'), ':');
        $case_when_item_alias .= ' ELSE ';
        $case_when_item_alias .= $a_id . ' END as slug';
        $query->select($case_when_item_alias);

        $case_when_category_alias = ' CASE WHEN ';
        $case_when_category_alias .= $query->charLength('c.nid');
        $case_when_category_alias .= ' THEN ';
        $c_id = $query->castAsChar('c.id');
        $case_when_category_alias .= $query->concatenate(array($c_id, 'c.nid'), ':');
        $case_when_category_alias .= ' ELSE ';
        $case_when_category_alias .= $c_id . ' END as catslug';
        $query->select($case_when_category_alias);

        /* ---------------------------------------------------------------------------- */

        $query->from('#__sobipro_object AS a');
// SECTION
        $query->innerJoin('#__srsobipro_tree AS t ON a.id = t.id AND ' . $catalog_path);

        $query->innerJoin('#__sobipro_object AS c ON a.parent = c.id'); // CATEGORY

        $query->leftJoin('#__users AS ow ON ow.id = a.owner');
        $query->leftJoin('#__users AS u ON u.id = a.updater');

        $query->innerJoin('#__sobipro_field_data AS n ON a.id = n.sid AND n.fid=' . $field_name[$catalog_pid]->sValue); // GET NAME FORM FIELD, NOT FROM OBJECT
///        $query->leftJoin('#__sobipro_relations AS rc ON a.id = rc.id and rc.oType=' . $_db->Quote('entry')); // CATEGORIES
///        $query->leftJoin('#__sobipro_language AS lc ON rc.pid = lc.id and lc.sKey=' . $_db->Quote('name')); // CATEGORIES
///        $query->leftJoin('#__sobipro_field_option_selected AS o ON a.id = o.sid'); // OPTIONS
///        $query->leftJoin('#__sobipro_language AS ol ON o.fid = ol.fid and ol.sKey=o.optValue AND ol.oType="field_option"'); // OPTIONS - Un-optimized due compatibility problem - see Jeroen

        $k = 0;
        foreach ($field_tables as $field_table) {
            $query->leftJoin($field_table); // FIELD TABLES TO CREATE A FULL ENTRY                
            $query->select($field_selects[$k]);
            $k++;
        }

        $whereq = 'a.oType=' . $_db->Quote('entry');
        $whereq = $whereq . ' AND c.oType=' . $_db->Quote('category');
///        $whereq = $whereq . ' AND lc.oType=' . $_db->Quote('category');
/// $whereq = $whereq . ' AND ol.oType="field_option"'; - Un-optimized due compatibility problem - see Jeroen
        $whereq = $whereq . ' AND a.state=1 AND c.state=1';
        $query->where($whereq);

        JLog::add("Query: " . $query, JLog::INFO);
        return $query;
    }

    function _loadFields($catalog_pid, $search_field_list) {
        $_db = JFactory::getDbo();

        $search_field_arr = explode(',', $search_field_list);
        $query_fields_search = '';
        if ((!empty($search_field_list)) && (count($search_field_arr) > 0)) {
            $query_fields_search = " AND fid IN (" . implode(",", $search_field_arr) . ") ";
        }

// Load text fields
        $query = $_db->getQuery(true);
        $query->select('fid');
        $query->select('nid');
        $query->from('#__sobipro_field AS f');
        $query->where('f.section=' . $_db->Quote($catalog_pid) .
                ' AND ( f.fieldType=' . $_db->Quote('inbox') .
                ' OR f.fieldType=' . $_db->Quote('select') .
                ' OR f.fieldType=' . $_db->Quote('textarea') . ')' .
                $query_fields_search);
        $query->order('fid');

        ////// JLog::add("Fields Query: " . $query, JLog::INFO);

        $_db->setQuery($query);
        $fields = $_db->loadObjectList();

        return $fields;
    }

    function _genSqlFieldTables($fields) {
        $_db = JFactory::getDbo();
        $field_tables = array();
        $i = 1;
        foreach ($fields as $field) {
            $field_tables[] = '#__sobipro_field_data AS f' . $i . ' ON a.id = f' . $i . '.sid AND f' . $i . '.fid = ' . $_db->Quote($field->fid);
            $i++;
        }
        return $field_tables;
    }

    function _genSqlFieldSelect($fields) {
        $field_selects = array();
        $i = 1;
        foreach ($fields as $field) {
            $field_selects[] = "f{$i}.baseData AS {$field->nid}";
            $i++;
        }
        return $field_selects;
    }

    function _checkLastRunRebuild() {
        if ($this->_checkLastRun()) {
            $catalog_list = $this->params->def('catalog_list', null);
            if (!empty($catalog_list)) {
                $catalogs = explode(',', $catalog_list);
            } else {
                $catalogs = $this->_getCatalogs();
            }

            if (!empty($catalogs)) {
                $this->_clean_tree();
                foreach ($catalogs as $catalog_pid) {
                    $this->rebuild_tree($catalog_pid, 0, 1, null, $catalog_pid);
                }
            }
        }
    }

    function _clean_tree() {
        $_db = JFactory::getDbo();
        $query = 'TRUNCATE TABLE `#__srsobipro_tree`;';
        $_db->setQuery($query);
        $_db->query();
    }

    function _checkLastRun() {
        $_db = JFactory::getDbo();
        $query = "SELECT last_update FROM " . $_db->nameQuote('#__srsobipro_tree') . " LIMIT 1";
        $_db->setQuery($query);
        $last_run = $_db->loadResult();
        $last_run = strtotime($last_run);

        $interval = 1; //$this->params->def('interval', 60);

        return (time() > $last_run + $interval * 60);
    }

// Storing Hierarchical Data in a Database Article
//   Modified Preorder Tree Traversal
//   Script that converts an adjacency list to a modified preorder tree traversal table
// Based on http://www.sitepoint.com/hierarchical-data-database/

    function rebuild_tree($node, $parent, $left, $path, $catalog_id) {
        $_db = JFactory::getDbo();

// the right value of this node is the left value + 1    
        $right = $left + 1;

// get all children of this node    
        $query = $_db->getQuery(true);
        $query->select('a.id');
        $query->from('#__sobipro_object AS a');
        $query->where(' a.parent=' . $_db->Quote($node) .
                ' AND a.state=1 ');

        $_db->setQuery($query);
        $rows = $_db->loadResultArray();
        foreach ($rows as $row) {
// recursive execution of this function for each    
// child of this node    
// $right is the current right value, which is    
// incremented by the rebuild_tree function    

            $right = $this->rebuild_tree($row, $node, $right, ($parent ? $path . $node . '-' : $node . '-'), $catalog_id);
        }

// we've got the left value, and now that we've processed    
// the children of this node we also know the right value    
        if ($parent)
            $query = "INSERT INTO `#__srsobipro_tree` VALUES($node, $parent, $left, $right, " . $_db->Quote($path) . ", $catalog_id, now());";
        $_db->setQuery($query);
        $_db->query();

// return the right value of this node + 1    
        return $right + 1;
    }

    function _createTables() {
        $_db = JFactory::getDbo();
        $query = 'DROP TABLE IF EXISTS `#__srsobipro_tree`;';
        $_db->setQuery($query);
        $_db->query();

        $query = 'CREATE TABLE IF NOT EXISTS `#__srsobipro_tree` (
  `id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `path` varchar(1024) NOT NULL,
  `section` int(11) NOT NULL,  
  `last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `path` (`path`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
        $_db->setQuery($query);
        $_db->query();
    }

    function _getCatalogs() {
        $_db = JFactory::getDbo();
        $query = 'SELECT id FROM `#__sobipro_object` WHERE parent=0 AND state=1 ORDER BY id';
        $_db->setQuery($query);
        return $_db->loadResultArray();
    }

    function _cleanListOfNumerics($listOfNumerics) {
        return preg_replace('/[^,0-9]/', '', $listOfNumerics);
    }

    function _getFieldNames($catalogs) {
        $catalogs_list = implode(',', $catalogs);
        $_db = JFactory::getDbo();
        $query = 'SELECT cfg.section, cfg.sValue FROM `#__sobipro_config` cfg WHERE cfg.section IN (' . $catalogs_list . ') AND cfg.sKey=' . $_db->Quote('name_field');
        $_db->setQuery($query);
        $results = $_db->loadObjectList('section');

        if ($debugq)
            echo "</br>DEBUG+: Field Names=" . print_r($results, true);

        return $results;
    }

}
