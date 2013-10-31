<?php
/**
* googleDirections_tohere plugin
* This plugin is an extension of the googleDirections plugin.
* You specify a designated place to be the destination,
* and let the user enter the adddress he or she is coming from.
* Google will then provide the driving or walking directions
* from the user-specified address to your designated place.
* Author: kksou
* Copyright (C) 2006-2008. kksou.com. All Rights Reserved
* Website: http://www.kksou.com/php-gtk2
* v1.5 May 30, 2009
*/

defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

#@session_start();

jimport( 'joomla.event.plugin' );
$joomla_ver = '1.7';

$path_info = pathinfo(dirname(__FILE__));
#$path_base = dirname(__FILE__);
$path_base = $path_info['dirname'];
global $googleMaps_util;
$googleMaps_util = $path_base.'/googleMaps/googleMaps.util.php';
if (file_exists($googleMaps_util)) {
	require_once($googleMaps_util);
} else {
	return 0;
}

list($googleMaps_lib, $googleDirections_lib, $googleDirections_tohere_lib) = get_paths($path_base);
$googleMaps_ver = get_googleMaps_ver($path_base);
$googleDirections_ver = get_googleDirections_ver($path_base);

global $gdir_lib_ok, $gdir2_lib_ok;
$gdir2_lib_ok = 0;
if (file_exists($googleMaps_lib)) {
	require_once($googleMaps_lib);
	if (file_exists($googleDirections_lib)) {
		require_once($googleDirections_lib);
		if (file_exists($googleDirections_tohere_lib)) {
			require_once($googleDirections_tohere_lib);
			$gdir2_lib_ok = 1;
			#if (!googleMaps_ver_ok($googleMaps_ver)) $gdir2_lib_ok = 3;
			if (! isset ( $googleMaps_ver ) || $googleMaps_ver < '010717') {
				$gdir2_lib_ok = 3;
				error_msg2('googleMaps-plugin.php', 'latest version of googleMaps plugin', 'googleDirections');
			}
			#if (!googleDirections_ver_ok($googleDirections_ver)) $gdir2_lib_ok = 4;
			if (! isset ( $googleDirections_ver ) || $googleDirections_ver < '010711') {
				$gdir2_lib_ok = 4;
				error_msg2('googleDirections.php', 'latest version of googleDirections plugin', 'googleDirections_tohere');
			}
		}
	} else {
		$gdir2_lib_ok = 2;
	}
} else {
	$gdir2_lib_ok = 0;
}

class plgContentgoogleDirections_tohere extends JPlugin {

	function plgContentgoogleDirections_tohere( &$subject, $params ) {
		parent::__construct( $subject, $params );
 	}

	#function onPrepareContent( &$row, &$params, $limitstart=0 ) {
 	function onContentPrepare( $context, &$row, &$params, $limitstart ) {
		
		global $googleMaps_util;
		if (! file_exists ( $googleMaps_util )) {
			print "<p style=\"background:#ffff00;padding:20px;line-height:4em\"><b>ttt2 ERROR >>> </b>You need to install the <a href=\"http://www.kksou.com/php-gtk2/Joomla-Gadgets/googleMaps-plugin.php#download\"><b>latest version of googleMaps plugin</b></a> for the googleDirections plugin to work.</p>";
			
			return false;
		}
		
 		global $gdir2_lib_ok;
		if ($gdir2_lib_ok == 3 || $gdir2_lib_ok == 4) return false;
		
		if ($gdir2_lib_ok==0) {
			error_msg2('googleMaps-plugin.php', 'googleMaps plugin', 'googleDirections_tohere');
			return false;
		}
		if ($gdir2_lib_ok==2) {
			error_msg2('googleDirections-plugin', 'googleDirections plugin', 'googleDirections_tohere');
			return false;
		}

		#$plugin =& JPluginHelper::getPlugin('content', 'googleDirections_tohere');
		$pluginParams = $this->params;

		/*if ( !$pluginParams->get( 'enabled', 1 ) ) {
			$row->text = preg_replace( $regex, '', $row->text );
			return true;
		}*/

		$param = new stdClass;
		$param->api_key = $pluginParams->get('api_key');
		$param->width = $pluginParams->get('width', 400);
		$param->height = $pluginParams->get('height', 480);
		$param->zoom = $pluginParams->get('zoom', 15);
		$param->dir_width = $pluginParams->get('dir_width', 275);
		$param->header_map = $pluginParams->get('header_map');
		$param->header_dir = $pluginParams->get('header_dir');
		$param->map_on_right = $pluginParams->get('map_on_right');
		$param->label_coming_from = $pluginParams->get('label_coming_from', 'Coming from');
		$param->label_get_directions = $pluginParams->get('label_get_directions', 'Get Directions');
		$param->addr_input_size = $pluginParams->get('addr_input_size', 42);

		$is_mod = 0;
		if (isset($params->is_mod)) $is_mod = 1;

		global $gdir2_lib_ok;
		if ($gdir2_lib_ok==1) {
			$plugin = new Plugin_googleDirections_tohere($row, $param, $is_mod);
		}
		
		return true;
	}
}

?>
