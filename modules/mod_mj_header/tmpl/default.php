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
/** @var $showTitle bool */
/** @var $siteTitle string */
/** @var $imgURL string */
?>

<?php if($showTitle): ?>

<?php if(!empty($siteTitle)): ?>
<h1><?php echo $siteTitle;?></h1>
<?php endif; ?>

<?php if(!empty($pageTitle)): ?>
<h2><?php echo $pageTitle;?></h2>
<?php endif; ?>

<?php else: ?>

<img src="<?php echo $imgURL;?>" alt="<?php echo $siteTitle;?>"/>

<?php endif; ?>