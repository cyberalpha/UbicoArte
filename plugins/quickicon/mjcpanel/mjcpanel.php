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
defined('_JEXEC') or die;

class plgQuickiconMjcpanel extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
//		$this->loadLanguage();
	}

	public function onGetIcons($context)
	{
		if($context != 'mod_quickicon' || !JFactory::getUser()->authorise('core.manage', 'com_mobilejoomla'))
			return null;

		return self::getIcons();
	}

	public static function getIcons()
	{
		if(!self::isMJInstalled())
			return array();

		JHTML::_('behavior.modal', 'a.modal');

		self::upgradeScientia();

		$document = JFactory::getDocument();
		$document->addStyleSheet('components/com_mobilejoomla/css/mod_mj_adminicon.css');

		$lang = JFactory::getLanguage();
		$lang->load('com_mobilejoomla', JPATH_ADMINISTRATOR);

		include_once JPATH_ADMINISTRATOR.'/components/com_mobilejoomla/mobilejoomla.html.php';
		HTML_MobileJoomla::CheckForUpdate();

		if(version_compare(JVERSION, '3.0', '>='))
		{
			$image_prefix = '';
			$image_suffix = '';
		}
		else
		{
			$image_prefix = JURI::base().'components/com_mobilejoomla/images/';
			$image_suffix = '.png';
		}

		return array(
			array(
				'id'    => 'mjnoupdate',
				'link'  => 'index.php?option=com_mobilejoomla',
				'image' => $image_prefix.'mj-cpanel'.$image_suffix,
				'text'  => JText::_('COM_MJ__MOBILEJOOMLA')
			),
			array(
				'id'    => 'mjupdate',
				'link'  => 'index.php?tmpl=component&option=com_mobilejoomla&task=update',
				'target'=> '_self" class="modal" rel="{handler: \'iframe\', size: {x: 480, y: 320}}',
				'image' => $image_prefix.'mj-update'.$image_suffix,
				'text'  => JText::_('COM_MJ__UPDATE_AVAILABLE')
			)
		);
	}

	private static function isMJInstalled()
	{
		return is_file(JPATH_ADMINISTRATOR.'/components/com_mobilejoomla/mobilejoomla.html.php');
	}

	private static function upgradeScientia()
	{
		$app = JFactory::getApplication();
		$installScientia = $app->getUserState( "com_mobilejoomla.scientiainstall", false );

		if($installScientia) :
?>
<script type="text/javascript">
window.addEvent('domready', function() {
	SqueezeBox.fromElement($('scientiapopup'), {parse:'rel'});
});
</script>
<a id="scientiapopup" style="display:none" href="components/com_mobilejoomla/scientia/index.php" rel="{handler: 'iframe', size: {x: 560, y: 380}}"></a>
<?php
		endif;
	}
}
