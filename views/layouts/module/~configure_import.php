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
				<th scope="row">Truncte DB</th>
				<td>
					<?= Nsm_site_generator_helper::yesNoRadioGroup("{$input_prefix}[general][truncate_db]", FALSE); ?>
					<!-- <?= lang('alert.warning.truncate_db'); ?> -->
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tg" id="channels">
	<h2>Which modules would you like to import?</h2>
	<div class="alert info">
	    <p>Check any of the modules below to import the channel, it's related fields, statuses, categories and templates.</p>
	</div>
	<table class="data NSM_Stripeable NSM_MagicCheckboxes">
		<thead>
			<tr style="white-space:nowrap">
				<th scope="col" style="width:100px;">Channel</th>
				<th scope="col">Description</th>
				<th scope="col">Field Group</th>
				<th scope="col">Status Group</th>
				<th scope="col">Category Group(s)</th>
				<th scope="col">Entries</th>
				<th scope="col" style="width:100px;">
					<input type="checkbox" class="NSM_MagicCheckboxesTrigger" style="float:right" /> Import
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($channels as $count => $channel) : ?>
    			<tr <?php if (in_array($channel['channel_name'], $existing_channels)) : ?>class="alert error"<?php endif; ?>>
				<th scope="row" style="vertical-align: top">
				    <?= $channel['channel_title'] ?>
				    <?php if (in_array($channel['channel_name'], $existing_channels)) : ?>
    				<span style="font-weight:normal; display:block; font-size:11px;" class="error">This channel already exists.</span>
    				<?php endif; ?>
				</th>
				<td><?= $channel['channel_description'] ?></td>
				<td style="white-space:nowrap; vertical-align: top">
					<?php if($custom_field_group = $xml->xpath("field_groups/group[@group_ref_id='{$channel['field_group']}']")) : ?>
						<?= $custom_field_group[0]["group_name"] ?>
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap; vertical-align: top">
					<?php if($status_group = $xml->xpath("status_groups/group[@group_ref_id='{$channel['status_group']}']")) : ?>
						<?= $status_group[0]["group_name"] ?>
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap; vertical-align: top">
					<?php foreach(explode("|", $channel['cat_group']) as $category_group_id) : ?>
					    <?php if($category_group = $xml->xpath("categoryGroups/group[@group_ref_id='{$category_group_id}']")) : ?>
    					    <?= $category_group[0]['group_name']; ?><br />
					    <?php endif; ?>
					<?php endforeach; ?>
				</td>
				<td style="white-space:nowrap; vertical-align: top; text-align:right">
				    <?= count($channel->entry); ?>
				</td>
				<td style="white-space:nowrap; vertical-align: top">
					 <input 
    					    style="float:right; margin-left:9px; margin-top:3px"
    						type="checkbox" 
    						class="NSM_MagicCheckboxesTrigger" 
    						name="<?=$input_prefix?>[channels][]" 
    						value="<?= $channel['channel_name'] ?>"
    					/>
                </td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="action" style="text-align:right">
	<input type="submit" class="submit" value="Begin Import" />
</div>