<?php

/**
 * View for Control Panel ext_settings Form
 * This file is responsible for displaying the user-configurable ext_settings for the NSM Multi Language extension in the ExpressionEngine control panel.
 *
 * @package			NsmBetterMeta
 * @version			1.1.3
 * @author			Leevi Graham <http://leevigraham.com> - Technical Director, Newism
 * @copyright 		Copyright (c) 2007-2012 Newism <http://newism.com.au>
 * @license 		Commercial - please see LICENSE file included with this distribution
 * @link			http://ee-garage.com/nsm-better-meta
 **/

$EE =& get_instance();

?>

<div class="mor">
	<?= form_open(
			'C=addons_extensions&M=extension_settings&file=' . $addon_id,
			array('id' => $addon_id . '_prefs'),
			array($input_prefix . "[enabled]" => TRUE)
		)
	?>

	<!-- 
	===============================
	Alert Messages
	===============================
	-->

	<?php if($error) : ?>
		<div class="alert error"><?php print($error); ?></div>
	<?php endif; ?>

	<?php if($message) : ?>
		<div class="alert success"><?php print($message); ?></div>
	<?php endif; ?>
	
	<div class="tg">
		<h2><?= lang("Bundle preferences"); ?></h2>
		<div class="alert info"><?= lang("bundle_preferences_info") ?></div>

        <table>
			<tbody>
				<tr class="even">
					<th scope="row"><?= lang("Server path to bundles"); ?></th>
					<td>
					    <input
							type="text"
							id=""
							value="<?= form_prep($data['bundle_server_path']) ?>"
							name="<?=$input_prefix?>[bundle_server_path]"
						/>
					</td>
				</tr>
			</tbody>
		</table>
	<!-- 
	===============================
	Submit Button
	===============================
	-->

	<div class="actions">
		<input type="submit" class="submit" value="<?php print lang('save_extension_settings') ?>" />
	</div>

	<?= form_close(); ?>
</div>