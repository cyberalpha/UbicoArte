<!DOCTYPE html>
<?php 
<?php 
/**
* @version		April 2012 2.5.0
* @package		Planroute
* @copyright		Copyright (C) 2005 - 2012 Bart Eversdijk. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Direct access is allowed for AJAX call handling -- Set up a Joomla! mockup environment first
// TO DO: make sure this call was send by module it self (integrety check)
define( '_JEXEC', 1 );
define( 'JPATH_BASE', realpath(dirname(__FILE__).'/../..' ));
define( 'DS', DIRECTORY_SEPARATOR );
define( '_IN_AJAXCALL', 1 );

// Make sure the BASE-path starts at the base of Joomla!
// Define this variable, because normal variable are cleared by the framework
define ('MODNAME', substr(dirname(__FILE__), strrpos(dirname(__FILE__), DS) + 1));
if (strpos(php_sapi_name(), 'cgi') !== false && !empty($_SERVER['REQUEST_URI'])) {
    $_SERVER['PHP_SELF'] =  str_replace ('/modules/'.MODNAME, '', $_SERVER['PHP_SELF']);
} else {
    $_SERVER['SCRIPT_NAME'] =  str_replace ('/modules/'.MODNAME, '',$_SERVER['SCRIPT_NAME']);
}
require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
require_once ( JPATH_BASE .DS.'libraries'.DS.'joomla'.DS.'html'.DS.'parameter.php' );

$mainframe 	=& JFactory::getApplication('site');
$mainframe->initialise();
$document	= &JFactory::getDocument();

$modid		= JRequest::getInt('modid', 0);

$user		=& JFactory::getUser();
$db		=& JFactory::getDBO();
$groups 	= JAccess::getGroupsByUser($user->get('id'));
$query 		= 'SELECT id, params'
		. ' FROM #__modules AS m'
		. ' WHERE m.published = 1'
		. ' AND m.id = '.(int)$modid
		. ' AND m.access in (' . implode(",", $groups) . ')'
		. ' AND m.module = "'.MODNAME.'"';
$db->setQuery( $query );
$mod     = $db->loadObject();

if ($mod == null)
{
    print "Access denied";
    exit;
}

$params  = new JParameter( $mod->params );
$baseurl = $params->get('googleurl', 'google.com');
$height  = $params->get('mapheight', '200');

$road   = JRequest::getString('road', 'car');
$saddr  = JRequest::getString('saddr', '');
$daddr  = JRequest::getString('daddr', '');
if ($daddr == '' || $daddr== '')
{
    print "Access denied";
    exit;
}
?>
<html lang="en">
<head>
<style>
div#directionsPanel
{
    font-family: Verdana;
    font-size: 10px;
}
div.adp-warnbox
{
    font-size: 10px;
}
div.adp-legal
{
    font-size: 8px;
}
</style>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<script src="http://maps.<?php print $baseurl; ?>/maps/api/js?sensor=false" type="text/javascript"></script>
<script type="text/javascript">
var directionsDisplay;
var directionsService = new google.maps.DirectionsService();
var map;

function initialize() {
  directionsDisplay = new google.maps.DirectionsRenderer();
  var myOptions = {
    zoom:7,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  }
  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
  directionsDisplay.setMap(map);
  directionsDisplay.setPanel(document.getElementById("directionsPanel"));

  var start = "<?php print preg_replace("/\(.*\)/", "", $_REQUEST['saddr']); ?>";
  var end   = "<?php print preg_replace("/\(.*\)/", "", $_REQUEST['daddr']); ?>";
  var request = {
    origin:start,
    destination:end,
    travelMode: google.maps.TravelMode.<?php
	switch ($road)
	{
	    case 'walk': 
	      print "WALKING";
	      break;
	    default: 
	      print "DRIVING";
	      break;
	}?>
  };
  directionsService.route(request, function(result, status) {
    if (status == google.maps.DirectionsStatus.OK) {
	directionsDisplay.setDirections(result);
    }
  });
}
</script>
</head>
<body onload="initialize()" style="margin:0; padding:0;">
     &nbsp;
     <a href="http://maps.<?php print $baseurl; ?>/maps?saddr=<?php print $saddr; ?>&daddr=<?php print $daddr; ?>&pw=2" target="_new"><img src="assets/print.png" alt="print directions" title="print directions" /></a>&nbsp;&nbsp;
     <a href="http://maps.<?php print $baseurl; ?>/maps?saddr=<?php print $saddr; ?>&daddr=<?php print $daddr; ?>" target="_new"><img src="assets/link.png" alt="open link" title="open link" /></a>&nbsp;&nbsp;
     <a href="?saddr=<?php print $daddr; ?>&daddr=<?php print $saddr; ?>&modid=<?php print $modid; ?>&road=<?php print $road; ?>"><img src="assets/return.png" alt="way back" title="way back" /></a>&nbsp;&nbsp;
     <a href="?saddr=<?php print $saddr; ?>&daddr=<?php print $daddr; ?>&modid=<?php print $modid; ?>&road=car"><img src="assets/car.png" alt="by car" title="by car" /></a>&nbsp;&nbsp;
     <a href="?saddr=<?php print $saddr; ?>&daddr=<?php print $daddr; ?>&modid=<?php print $modid; ?>&road=walk"><img src="assets/walk.png" alt="walking" title="walking" /></a>&nbsp;&nbsp;
    <div id="map_canvas" style="width: 70%; height: <?php print $height; ?>px; float: right;"></div>
    <div id="directionsPanel" style="overflow-y: auto; width: 30%; height: <?php print ($height - 20); ?>px;"></div>
</body>
</html>
