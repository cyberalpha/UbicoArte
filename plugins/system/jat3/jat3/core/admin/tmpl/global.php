<?php
/**
 * ------------------------------------------------------------------------
 * JA T3v2 System Plugin for J25 & J31
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2011 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * ------------------------------------------------------------------------
 */

// No direct access
defined('_JEXEC') or die;

foreach ($fieldSets as $name => $fieldSet) :?>
    <fieldset class="panelform">
        <table width="100%" cellspacing="1" class="paramlist admintable">
            <tbody>
                <?php foreach ($configform->getFieldset($name) as $field) : ?>
                    <tr>
                        <?php if ($field->label != '') {?>
                        <td width="40%" class="paramlist_key">
                            <?php echo $field->label; ?>
                        </td>
                        <?php }?>
                        <td class="paramlist_value" <?php if ($field->label=='' ){?> colspan="2" <?php }?>>
                            <?php echo $field->input; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </fieldset>
<?php
endforeach;
?>