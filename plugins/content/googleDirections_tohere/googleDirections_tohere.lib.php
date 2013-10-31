<?php
/**
* googleDirections_tohere.lib
* This plugin is an extension of the googleDirections plugin.
* You specify a designated place to be the destination,
* and let the user enter the adddress he or she is coming from.
* Google will then provide the driving or walking directions
* from the user-specified address to your designated place.
* Author: kksou
* Copyright (C) 2006-2008. kksou.com. All Rights Reserved
* Website: http://www.kksou.com/php-gtk2
* v1.5 May 30, 2009
* v1.51 July 24, 2009 Allow multiple lines in home_label
* v1.52 July 31, 2009 bug fix: <br /> appears in destination
* v1.53 October 2, 2009 bug fix: Undefined variable: map_id in /var/www/html/rcl122/plugins/content/googleDirections_tohere/googleDirections_tohere.lib.php on line 126
* August 15, 2009 allow user to specify default country
* June 15, 2010 allow user to display multiple maps with to_here directions
* v1.64 Oct 20, 2011 support for Joomla 1.6/1.7 and PHP 5.3.8
* v1.75 Nov 11, 2011 uses googleMaps API v3!
* v1.76 Dec 24, 2011 support for multiple stopovers!
* v1.77 Jan 03, 2012 1) now gives exact location when lat/lng is given
*                    2) user can now press enter to get the direction (instead of clicking the button)
* v1.78 Feb 01, 2012 1) dded support for Joomla 2.5
*                    2) add flag w3c=1 => w3c compliant
*                    3) support for IE7!
*                    4) allow googleMaps to display in tabs
*                    5) now allows address to include ' (apostrophe)
*                    6) support for vertical alignment
*/

class Plugin_googleDirections_tohere extends Plugin_googleDirections_base {

	function Plugin_googleDirections_tohere( &$row, $pluginParams, $is_mod=0 ) {
		$this->mod = 'gdir_tohere';
		$this->tag = 'googleDir_tohere';
		$this->css = 'googleDirections_tohere.css';
		$this->addoverview = '';
		$this->addgoogle = '';
		$this->params = $pluginParams;

		$this->init_google_maps($row, $pluginParams, $is_mod);
	}

	function process_additional_param(&$row, $matches, $map_id) {
		$this->home = '';
		$this->home_label = '';
		$this->home_lat = 0;
		$this->home_long = 0;
		$this->home_addr = '';

		$this->label_coming_from = $this->params->label_coming_from;
		$this->label_get_directions = $this->params->label_get_directions;
		$this->addr_input_size = $this->params->addr_input_size;
		$this->default_country = '';

		#if (preg_match('/home="([^"]+)"/', $this->fix_str2($matches[1]), $matches2)) $this->home = $this->fix_str2($matches2[1]);
		$this->home = $this->fix_str3($this->home);
		if (preg_match('/home_lat=([\+\-]?[0-9\.]+)/', $matches[1], $matches2)) $this->home_lat = $matches2[1];
		if (preg_match('/home_long=([\+\-]?[0-9\.]+)/', $matches[1], $matches2)) $this->home_long = $matches2[1];
		if (preg_match('/default_country="([^"]+)"/', $matches[1], $matches2)) $this->default_country = $matches2[1];
		if (preg_match('/home_label="([^"]+)"/', $this->fix_str2($matches[1]), $matches2)) $this->home_label = $this->fix_str2($matches2[1]);
		#$this->home_label = $this->fix_str3($this->home_label);
		$this->home_label = $this->fix_str3a($this->home_label);
		if ($this->default_country!='') $this->default_country = ', '.$this->default_country;

		if (preg_match('/home_addr="([^"]+)"/', $this->fix_str2($matches[1]), $matches2)) $this->home_addr = $this->fix_str2($matches2[1]);
		$this->home_addr = $this->fix_str3($this->home_addr);

		if ($this->vertical && preg_match('/width=(\d+%)/', $matches[1], $matches2)) $this->width = $matches2[1];
		#if (preg_match('/(\d+%)/', $this->width, $matches3)) $this->width = $matches2[1];
	}

	function fix_str3a($str) {
		$str = str_replace('~', '<br />', $str);
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\n", "<br />", $str);
		return $str;
	}

	function output_map(&$row, $matches, $map_id) {
		if ($this->width<10 || $this->width>4096) $this->width = 400;
		if ($this->dir_width<10 || $this->dir_width>4096) $this->dir_width = 275;

		if (preg_match('/%/', $this->width)) $width2 = $this->width;
		else $width2 =  $this->width.'px';

		if (preg_match('/%/', $this->dir_width)) $dir_width2 = $this->dir_width;
		else $dir_width2 =  $this->dir_width.'px';

		$this->to = '';
		$val = '';
		//if ($map_id==0) $val = '701 First Avenue, Sunnyvale, CA 94089';
		//if ($map_id==1) $val = '701 First Avenue, Sunnyvale, CA 94089';
		//if ($map_id==1) $val = '39210 Fremont Hub #211, Fremont, CA 94538';
		//if ($map_id==2) $val = '456 University Ave., Palo Alto, CA 94301';
		$home_addr = $this->home_addr;
		if ($home_addr=='') $home_addr = $this->home_lat.','.$this->home_long;
		$br = "<br />";
		if ($this->vertical) $br = '';

		$dir_form = "<div id=\"{$this->mod}_form{$map_id}\">
		<span id=\"{$this->mod}_form_label{$map_id}\">{$this->label_coming_from}: </span>
		<span id=\"{$this->mod}_form_input{$map_id}\"><input type=\"text\" id=\"start{$map_id}\" size=\"$this->addr_input_size\" value=\"$val\" onkeypress=\"return submitenter(this,event,$map_id)\" /></span>$br
	    <button id=\"gdir_button{$map_id}\" onclick=\"get_dir('$map_id', '$this->home_label', 0, 0, '$home_addr', $this->startzoom, '$this->kml', '$this->to', '$this->mode', '$this->control', '$this->maptype', '$this->marker', '$this->addoverview', '$this->addscale', '$this->addgoogle', '$this->streetview', '$this->stopover', '$this->unit')\"><span id=\"{$this->mod}_form_button_label{$map_id}\">$this->label_get_directions</span></button></div>";
		#<button onclick=\"get_dir('$map_id', '$this->home_label', $this->home_lat, $this->home_long, '$this->home_addr', $this->startzoom, '$this->kml', '$this->to', '$this->mode', '$this->control', '$this->maptype', '$this->marker', '$this->addoverview', '$this->addscale', '$this->addgoogle', '$this->streetview', '$this->stopover')\">$this->label_get_directions</button></div>
		$errmsg = "<div id=\"{$this->mod}_errmsg{$map_id}\"></div>";
		$dir_div = "    $dir_form<div id=\"{$this->mod}_gdir{$map_id}\" style=\"width: {$dir_width2}\"></div>$errmsg\n";
		#$dir_div_td = "    <td valign=\"top\">".$dir_div."</td>\n";
		$dir_div_td = "    <td style=\"vertical-align:top\">".$dir_div."</td>\n";

		$output = '';
		if ($this->vertical) {	
			if ($this->add_p || $this->w3c) $output .= "</p>";
			$width3 = $width2;
			if ($this->map_full_width==1) $width3 = '100%';
			$output .= "    <div class=\"gdir_body\" id=\"{$this->mod}_gmap{$map_id}\" style=\"width: {$width3}; height: {$this->height}px\"></div>";
			$output .= "<div style=\"width: {$width3};\"><p align=\"right\" style=\"padding:0 0 0 0;margin:0 0 0 0\"><a href=\"http://www.kksou.com/php-gtk2/Joomla-Gadgets/googleDirections-To-Here-plugin.php\" style=\"color:#aaa;text-decoration: none;font-family:Tahoma, Arial, Helvetica, sans-serif;font-size:7pt;font-weight: normal;\">Powered by JoomlaGadgets</a></p></div>\n";
			if (!$this->hide_direction_text) $output .= $dir_div;
			if ($this->add_p || $this->w3c) $output .= "<p>";
		} else {
			if ($this->add_p || $this->w3c) $output .= "</p>";
			$output .= "\n<table class=\"googleDirections_tohere\">\n";
			if ($this->dir_on_right || !$this->map_on_right) {
				$output .= "<tr><th>$this->header_map</th><th>$this->header_dir</th></tr>\n";
			} else {
				$output .= "<tr><th>$this->header_dir</th><th>$this->header_map</th></tr>\n";
			}
			//$this->to = $this->home_label.'@'.$this->home_lat.','.$this->home_long;

			$output .= "<tr>\n";
			if (!($this->dir_on_right || !$this->map_on_right)) $output .= $dir_div_td;
			$output .= "    <td style=\"vertical-align:top\"><div class=\"gdir_body\" id=\"{$this->mod}_gmap{$map_id}\" style=\"width: {$width2}; height: {$this->height}px\"></div>";
			$output .= "<div style=\"width: {$width2};\"><p align=\"right\" style=\"padding:0 0 0 0;margin:0 0 0 0\"><a href=\"http://www.kksou.com/php-gtk2/Joomla-Gadgets/googleDirections-To-Here-plugin.php\" style=\"color:#aaa;text-decoration: none;font-family:Tahoma, Arial, Helvetica, sans-serif;font-size:7pt;font-weight: normal;\">Powered by JoomlaGadgets</a></p></div>\n";
			$output .= "</td>\n";
			if ($this->dir_on_right || !$this->map_on_right) $output .= $dir_div_td;
			$output .= "</tr></table>\n";
			if ($this->add_p || $this->w3c) $output .= "<p>";
		}

		$row->text = str_replace($matches[0], $output, $row->text);

		# added 2011.12.25
		# if only lat and lng are given, put home_addr as lat,lng
		if ($this->home_addr!='') $addr = $this->home_addr;
		else $addr = $this->home_lat.','.$this->home_long;

		$js = "init_{$this->mod}('$map_id', '$this->home_label', 0, 0, '$addr', $this->startzoom, '$this->kml', '$this->mode', '$this->control', '$this->maptype', '$this->marker', '$this->addoverview', '$this->addscale', '$this->addgoogle', '$this->streetview', '$this->stopover');\n";

		return $js;
	}

	function setup_css() {
		$css_file = dirname(__FILE__).'/'.$this->css;
		$css = file_get_contents($css_file);

		/*$output = "
<style type=\"text/css\">
<!--
$css
-->
</style>
";*/
		#return $output;
		return $css;
	}

	function setup_gmap() {
		$output = "\n\n<!-- \nPowered by Joomla Gadgets from kksou.com
http://www.kksou.com/php-gtk2/Joomla-Gadgets/googleDirections-To-Here-plugin.php
-->\n\n";

		$lang = '';
		if ($this->lang!='') $lang = "&amp;hl=".$this->lang;
		$output .= "\n"."<script type=\"text/javascript\" src=\"http://maps.googleapis.com/maps/api/js?sensor=false&amp;language=".$this->lang.'"></script>';

		$output .= $this->gmap_code();

		$output .= $this->gdir_code();

		$output .= "\n<script type=\"text/javascript\">
<!--
function submitenter(myfield,e,id) {
	var keycode;
	if (window.event) keycode = window.event.keyCode;
	else if (e) keycode = e.which;
	else return true;

	if (keycode == 13) {
	   var button = document.getElementById(\"gdir_button\"+id);
	   //myfield.form.submit();
	   button.click();
	   return false;
	} else {
	   return true;
	}
}

function init_{$this->mod}(id, home_label, centerLatitude, centerLongitude, home_addr, startZoom, kml, mode, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview) {
	init_{$this->mod}_gmap(id, home_addr, centerLatitude, centerLongitude, startZoom, home_label, kml, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview);
}

function get_dir(id, home_label, centerLatitude, centerLongitude, home_addr, startZoom, kml, to, mode, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview, stopover, unit) {
    var to2 = to.replace(/<br \/>/g, ', ');
    var from_addr = document.getElementById('start'+id).value + '$this->default_country';
    var len = from_addr.length;
    if (from_addr=='') {
        document.getElementById(\"{$this->mod}_gdir\"+id).innerHTML = '';
        document.getElementById(\"{$this->mod}_errmsg\"+id).innerHTML = '';
    	init_{$this->mod}_gmap(id, home_addr, centerLatitude, centerLongitude, startZoom, home_label, kml, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview);
    } else {
    	var geocoder = new google.maps.Geocoder();
 	    geocoder.geocode( { 'address': from_addr}, function(results, status) {
		    if (status == google.maps.GeocoderStatus.OK) {
		    	document.getElementById(\"{$this->mod}_errmsg\"+id).innerHTML = '';
			    document.getElementById(\"{$this->mod}_gdir\"+id).innerHTML = '';
		      	display_{$this->mod}(id, centerLatitude, centerLongitude, startZoom, kml, from_addr, home_addr, mode, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview, stopover, unit);
		    } else {
		    	document.getElementById(\"{$this->mod}_errmsg\"+id).innerHTML = '<br /><p><font color=#F74873>Google cannot decode your address:</font> <b style=\"background:#ffff00\">'+from_addr+'</b></p>';
			    document.getElementById(\"{$this->mod}_gdir\"+id).innerHTML = '';
		        init_{$this->mod}_gmap(id, home_addr, centerLatitude, centerLongitude, startZoom, home_label, kml, control, maptype, show_marker, addoverview, addscale, addgoogle, streetview);
		    }
	    });

    }
}
-->
</script>
";
		return $output;
	}
}

?>
