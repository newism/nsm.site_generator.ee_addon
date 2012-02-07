<!-- <ul class='menu tabs'>
	<li><a href='#channels'><?= lang("IA + Templates") ?></a></li>
	<li><a href='#design_theme'><?= lang("Theme") ?></a></li>
	<li><a href='#module_list'><?= lang("Addons") ?></a></li>
	<li><a href='#options'><?= lang("Other Options") ?></a></li>
	<li><a href='#show_all'><?= lang("Show all") ?></a></li>
</ul> -->
<div id="import-options" class="tg">
	<h2>Import Options</h2>
	<table class="data">
		<tbody>
			<tr>
				<th scope="row" rowspan="2">
					Truncte DB
				</th>
				<td>
					<?= Nsm_site_generator_addon::yesNoRadioGroup("{$input_prefix}[general][truncate_db]", FALSE); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tg" id="channels">
	<h2>Which channels would you like to import?</h2>
	<div class="alert info">Check any of the options below to import the channel, it's related categories, statuses and custom fields as defined in the XML config.</div>
	<table class="data NSM_Stripeable NSM_MagicCheckboxes">
		<thead>
			<tr style="white-space:nowrap">
				<th scope="col" style="width:100px;">Channel</th>
				<th scope="col">Notes</th>
				<th scope="col">Field Group</th>
				<th scope="col">Status Group</th>
				<th scope="col">Category Group</th>
				<th scope="col" style="width:100px;">
					<input type="checkbox" class="NSM_MagicCheckboxesTrigger" /> Import
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($channels as $count => $channel) : ?>
			<tr>
				<th><?= $channel['channel_title'] ?></th>
				<td><?= $channel->description[0] ?></td>
				<td style="white-space:nowrap">
					<?php if($custom_field_group = $xml->xpath("custom_field_groups/group[@id='{$channel['field_group']}']")) : ?>
						<?= $custom_field_group[0]["group_name"] ?>
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap">
					<?php if($status_group = $xml->xpath("status_groups/group[@id='{$channel['status_group']}']")) : ?>
						<?= $status_group[0]["group_name"] ?>
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap">
					<?php if($category_group = $xml->xpath("category_groups/group[@id='{$channel['cat_group']}']")) : ?>
						<!--a href="#cat_group-<?= $category_group[0]['id'] ?>"--><?= $category_group[0]["group_name"] ?><!-- /a -->
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap">
					<?php if (in_array($channel['channel_name'], $existing_channels)) : ?>
					This channel already exists.
					<?php else: ?>
					<input 
						type="checkbox" 
						class="NSM_MagicCheckboxesTrigger" 
						name="<?=$input_prefix?>[channels][]" 
						value="<?= $channel['channel_name'] ?>"
					/>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="action">
	<input type="submit" class="submit" value="Begin Import" />
</div>