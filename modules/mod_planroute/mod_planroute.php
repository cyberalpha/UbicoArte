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

// no direct access
defined('_JEXEC') or die;

// Get module parameters
$headertxt 	= 	 $params->get( 'headertxt', 	"Give your address and we plan you a route to us" );
$streettxt 	= 	 $params->get( 'addresstxt', 	"Your address here" );
$citytxt 	= 	 $params->get( 'citytxt', 	"Your city here" );
$warntxt 	= 	 $params->get( 'warntxt', 	"Please enter a valid departure location!" );
$desttxt 	= 	 $params->get( 'desttxt', 	"Plan route to/from:" );
$destination 	= 	 $params->get( 'destination', 	"Markt, Waalre, Nederland (blokhut)" );
$country 	= 	 $params->get( 'countrytxt', 	"Nederland" );
$showcountry 	= intval($params->get( 'showcountry', 	1 ));
$buttontxt 	= 	 $params->get( 'buttontxt', 	"Plan route now" );
$showbutton 	= intval($params->get( 'showbutton', 	1 ));
$buttonrevtxt 	= 	 $params->get( 'buttonrevtxt', 	"Plan way back" );
$showrevbutton 	= intval($params->get( 'showrevbutton', 0 ));
$inputboxwidth 	= intval($params->get( 'inputboxwidth', 175 ));
$mapwidth 	= intval($params->get( 'mapwidth', 	800 ));
$mapheight 	= intval($params->get( 'mapheight', 	640 ));
$sfx		= 	 $params->get( 'moduleclass_sfx', "" );
$target 	= 	 $params->get( 'target', 	"_blank" );
$translate 	= intval($params->get( 'translate', 	0 ));
$googleurl 	= 	 $params->get( 'googleurl', 	"google.com" );

$destinations	= preg_split('/\n/', $destination,-1,PREG_SPLIT_NO_EMPTY);
reset($destinations);

if (!($multiple_dest = (count($destinations) > 1))) {
    $destination = preg_replace("[\n\r]", "", $destination); // remove line breaks
    if (strpos($destination, '|') !== false) {		
        list($label, $val) = explode('|', $destination, 2);
	$destination = $val." (".$label.")";
    }
}

if ($translate) {
    $headertxt 		=  JText::_( $headertxt );
    $desttxt 		=  JText::_( $desttxt );
    $streettxt 		=  JText::_( $streettxt );
    $citytxt 		=  JText::_( $citytxt );
    $warntxt 		=  JText::_( $warntxt );
    $buttontxt 		=  JText::_( $buttontxt );
    $buttonrevtxt 	=  JText::_( $buttonrevtxt );
}

// Make string HTML and JAVA save
$jsstreettxt  = str_replace("'", "`", htmlspecialchars(stripslashes($streettxt)));
$jscitytxt    = str_replace("'", "`", htmlspecialchars(stripslashes($citytxt)));
$valuestr     = "value=\"%s\" onfocus=\"if (this.value=='%s') {this.value='';}\" onblur=\"if (this.value=='') {this.value='%s';}\"";
$valuestreet  = str_replace ("%s", $jsstreettxt,  $valuestr);
$valuecity    = str_replace ("%s", $jscitytxt,    $valuestr);

$streettxt    = str_replace("'", "`", stripslashes($streettxt));
$citytxt      = str_replace("'", "`", stripslashes($citytxt));
$country      = htmlspecialchars(stripslashes($country), ENT_QUOTES);

$headertxt    =  htmlspecialchars(stripslashes($headertxt), ENT_QUOTES);
$desttxt      =  htmlspecialchars(stripslashes($desttxt), ENT_QUOTES);
$buttontxt    =  htmlspecialchars(stripslashes($buttontxt), ENT_QUOTES);
$buttonrevtxt =  htmlspecialchars(stripslashes($buttonrevtxt), ENT_QUOTES);


switch ($target)  {
    case 'lightbox':
    //------------------------- LightBox target -----------------------
    $myparams['onOpen'] = '\function(el) {if (no_valid_address'.$module->id.') {alert(\''.addslashes($warntxt).'\'); this.close();}}';
    $myparams['iframeOptions'] = array("scrolling" => "no");
    JHTML::_('behavior.modal', 'a.modal-button', $myparams );
?>
    <script type="text/javascript">
        var no_valid_address<?php print $module->id; ?> = true;

	function link_route_<?php print $module->id; ?>( formObj, linkObj, wayback )
	{
	    if (formObj.address.value == '' || formObj.address.value == '<?php print $streettxt; ?>' || 
	       formObj.city.value == '' || formObj.city.value == '<?php print $citytxt; ?>' || formObj.country.value == '')
	    {
		//alert("<?php print addslashes($warntxt); ?>");
		linkObj.href= '';
	        no_valid_address<?php print $module->id; ?> = true;
		
		return false;
	    }
	    no_valid_address<?php print $module->id; ?> = false;
	   
<?php if ($multiple_dest) { ?>
	    ourAdr = encodeURI(formObj.destination.value);
<?php } else { 
	    if (strpos($destination, '|') === true) {
	        list($label, $val) = explode('|', $destination, 2);
		$destination = $val." (".$label.")";
	    }
?>
	    ourAdr = '<?php print urlencode($destination); ?>';
<?php } ?>	   
	    yourAdr = encodeURI(formObj.address.value+', '+formObj.city.value+', '+formObj.country.value);

	    if (wayback) {
		str = '<?php print JURI::base(); ?>modules/mod_planroute/gmapsloader.php?saddr='+ourAdr+'&daddr='+yourAdr+'&modid=<?php print $module->id; ?>';
	    } else {
		str = '<?php print JURI::base(); ?>modules/mod_planroute/gmapsloader.php?saddr='+yourAdr+'&daddr='+ourAdr+'&modid=<?php print $module->id; ?>';
	    }
	    linkObj.href= str;
	}
    </script>
<?php
    $formaction = 'action="#" ';
    $html_planbutton = <<<EOD
	    <a class="modal-button" title="press" href="#" onclick="return link_route_{$module->id}(document.route_{$module->id}, this, 0);" rel="{handler: 'iframe', size: {x: {$mapwidth}, y: {$mapheight}}}">
			<input class="button{$sfx}" value="{$buttontxt}" type="button" style="margin-top: 2px;" onclick="return false;" />
		</a>
EOD;
    $html_revbutton = <<<EOD
	    <a class="modal-button" title="press" href="#" onclick="return link_route_{$module->id}(document.route_{$module->id}, this, 1);" rel="{handler: 'iframe', size: {x: {$mapwidth}, y: {$mapheight}}}">
			<input class="button{$sfx}" value="{$buttonrevtxt}" type="button" style="margin-top: 2px;" onclick="return false;" />
		</a>
EOD;
	break;

    case 'popup':
    //------------------------- PopUp target -----------------------
?>
    <script type="text/javascript">
	function pop_route_<?php print $module->id; ?>( formObj, wayback )
	{
	    if (formObj.address.value == '' || formObj.address.value == '<?php print $streettxt; ?>' || 
	       formObj.city.value == '' || formObj.city.value == '<?php print $citytxt; ?>' || formObj.country.value == '')
	    {
		alert("<?php print addslashes($warntxt); ?>");
		return;
	    }
<?php if ($multiple_dest) { ?>
	    ourAdr = encodeURI(formObj.destination.value);
<?php } else { 
	    if (strpos($destination, '|') === true) {
	        list($label, $val) = explode('|', $destination, 2);
		$destination = $val." (".$label.")";
	    }
?>
	    ourAdr = '<?php print urlencode($destination); ?>';
<?php } ?>	   
	    yourAdr = encodeURI(formObj.address.value+', '+formObj.city.value+', '+formObj.country.value);
	    if (wayback) {
		str = 'http://maps.<?php print $googleurl; ?>/maps?saddr='+ourAdr+'&daddr='+yourAdr;
	    } else {
		str = 'http://maps.<?php print $googleurl; ?>/maps?saddr='+yourAdr+'&daddr='+ourAdr;
	    }
	    window.open(str,'win2','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=<?php print $mapwidth;?>,height=<?php print $mapheight;?>,directories=no,location=no'); 
	}
    </script>
<?php

    $formaction = 'action="#" ';
    $html_planbutton = '<input class="button'.$sfx.'" value="'.$buttontxt.'" type="button" style="margin-top: 2px;" onclick="return pop_route_'.$module->id.'(document.route_'.$module->id.', 0);return false;" />';

    $html_revbutton = '<input class="button'.$sfx.'" value="'.$buttonrevtxt.'" type="button" style="margin-top: 2px;" onclick="return pop_route_'.$module->id.'(document.route_'.$module->id.', 1);return false;" />';
	break;


    default:
    //------------------------- other targets -----------------------
?>
    <script type="text/javascript">
	var wayback = 0;
	function submit_route_<?php print $module->id; ?>( formObj )
	{
	    if (formObj.address.value == '' || formObj.address.value == '<?php print $streettxt; ?>' || 
	       formObj.city.value == '' || formObj.city.value == '<?php print $citytxt; ?>' || formObj.country.value == '')
	    {
		alert("<?php print addslashes($warntxt); ?>");
		return;
	    }
<?php if ($multiple_dest) { ?>
	    ourAdr = formObj.destination.value;
<?php } else { 
	    if (strpos($destination, '|') === true) {
	        list($label, $val) = explode('|', $destination, 2);
		$destination = $val." (".$label.")";
	    }
?>
	    ourAdr = '<?php print htmlspecialchars($destination); ?>';
<?php } ?>	    
	    yourAdr = formObj.address.value+', '+formObj.city.value+', '+formObj.country.value;
	    if (!wayback) {
		formObj.saddr.value = yourAdr;
		formObj.daddr.value = ourAdr;
	    } else {
		formObj.saddr.value = ourAdr;
		formObj.daddr.value = yourAdr;
	    }
	    formObj.submit();
	}
    </script>
<?php

	$formaction = 'action="http://maps.'.$googleurl.'/maps" target="'.$target.'" onsubmit="submit_route_'.$module->id.'(this);return false;"';
	$html_planbutton = '<input class="button'.$sfx.'" value="'.$buttontxt.'" type="submit" style="margin-top: 2px;" onclick="wayback=0;" />';
	$html_revbutton = '<input class="button'.$sfx.'" value="'.$buttonrevtxt.'" type="submit" style="margin-top: 2px;" onclick="wayback=1;" />';
	break;
}
?>

<div align="center" class="planroute<?php print $sfx; ?>">
    <form <?php print $formaction; ?> method="get" name="route_<?php print $module->id; ?>" id="route_<?php print $module->id; ?>">
	<span class="planrouteheader<?php print $sfx; ?>"><?php print htmlspecialchars($headertxt); ?></span><br />
	<input type="text" class="inputbox<?php print $sfx; ?>" style="width:<?php print $inputboxwidth; ?>px" name="address" id="address" <?php print $valuestreet; ?> /><br />
	<input type="text" class="inputbox<?php print $sfx; ?>" style="width:<?php print $inputboxwidth; ?>px" name="city" id="city" <?php print $valuecity; ?> /><br />
<?php if ($showcountry) { ?>
	<input type="text" class="inputbox<?php print $sfx; ?>" style="width:<?php print $inputboxwidth; ?>px" name="country" id="country" value="<?php print $country; ?>" /><br />
<?php } else { ?>	
	<input type="hidden" name="country" id="country" value="<?php print $country; ?>" />
<?php }
      if ($multiple_dest) { ?>
<span class="planroutedestination<?php print $sfx; ?>"><?php print $desttxt; ?></span>
	<select class="inputbox<?php print $sfx; ?>" name="destination" id="destination" style="width:<?php print $inputboxwidth; ?>px">
<?php   foreach ($destinations as $dest) {
    	     $dest = preg_replace("[\n\r]", "", $dest); // remove (remaining) line breaks
	     list($label, $val) = explode('|', $dest, 2);
	     if ($val == '') { 
		     $val = $label; 
	     } else {
		     if (strpos($val, '(') === false || strpos($val, ')') === false ) {
			$val = $val." (".trim($label).")";
		     }
	     }
	     printf('<option value="%s">%s</option>\n', htmlspecialchars(trim($val)), htmlspecialchars(trim($label)));
	}
?>
	</select><br />
<?php }
	if ($showbutton) { print $html_planbutton; }
	if ($showrevbutton) { print $html_revbutton; }
?>
	<input type="hidden" name="saddr" value="" />
	<input type="hidden" name="daddr" value="" />
   </form>
</div>
