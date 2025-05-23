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

abstract class JHtmlEmail
{
	public static function cloak($mail, $mailto = 1, $text = '', $email = 1)
	{
		if($mailto)
			$html = '<a href="javascript:void(location.href=\'mail\'+\'to:'.str_replace('@', "'+'@'+'", $mail).'\')">'.($text ? $text : str_replace('@', '(at)', $mail)).'</a>';
		else
			$html = str_replace('@', '(at)', $mail);

		return $html;
	}
}
