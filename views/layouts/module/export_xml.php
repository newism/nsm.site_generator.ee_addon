<?php $EE =& get_instance(); ?>
<?xml version="1.0" encoding="UTF-8"?>
<generator_template
		title="<?= $bundle['title'] ?>"
		download_url="<?= $bundle['download_url'] ?>"
		version="<?= $bundle['version'] ?>"
>
	<description><![CDATA[ <?= $bundle['description'] ?> ]]></description>
	<post_import_instructions><![CDATA[ <?= $bundle['post_import_instructions'] ?> ]]></post_import_instructions>

	<authors>
<?php foreach ($bundle['authors'] as $author): ?>
		<author name="<?= $bundle['name'] ;?>" url="<?= $bundle['url'] ;?>"/>
<?php endforeach; ?>
	</authors>

	<requirements>
<?php foreach ($bundle['requirements'] as $requirement): ?>
		<author 
			type="<?= $requirement['type'] ;?>"
			title="<?= $bundle['title'] ;?>" 
			version="<?= $bundle['version'] ;?>"
			download_url="<?= $bundle['download_url'] ;?>"
		/>
<?php endforeach; ?>
	</requirements>

	<field_groups>
<?php foreach($field_groups as $group_data) : ?>
		<group <?php foreach($group_data as $key => $value) : if("fields" != $key) : ?> <?= $key ?>="<?= $value ?>"<?php endif; endforeach; ?>>
<?php foreach($group_data['fields'] as $field_id => $field_data) : ?>
			<field <?php foreach($field_data as $field_key => $field_value) : ?><?= $field_key ?>="<?= $field_value ?>" <?php endforeach; ?>/>
<?php endforeach; ?>
		</group>
<?php endforeach; ?>
	</field_groups>

	<status_groups>
<?php foreach($status_groups as $status_group_data) : ?>
		<group <?php foreach($status_group_data as $status_group_data_key => $status_group_data_value) : if("statuses" != $status_group_data_key) : ?> <?= $status_group_data_key ?>="<?= $status_group_data_value ?>"<?php endif; endforeach; ?>>
<?php foreach($status_group_data['statuses'] as $status_id => $status_data) : ?>
			<status <?php foreach($status_data as $status_data_key => $status_data_value) : ?><?= $status_data_key ?>="<?= $status_data_value ?>" <?php endforeach; ?>/>
<?php endforeach; ?>
		</group>
<?php endforeach; ?>
	</status_groups>

	<channels>
<?php foreach($channels as $channel_data) : ?>
		<channel <?php foreach($channel_data as $channel_data_key => $channel_data_value) : if("channel_entries" != $channel_data_key) : ?> <?= $channel_data_key ?>="<?= $channel_data_value ?>"<?php endif; endforeach; ?>>
<?php foreach($channel_data['channel_entries'] as $channel_entry_id => $channel_entry_data) : if("custom_fields" != $channel_data_key) :	?>
			<entry <?php foreach($channel_entry_data as $channel_entry_data_key => $channel_entry_data_value) : ?><?= $channel_entry_data_key ?>="<?= $channel_entry_data_value ?>" <?php endforeach; ?>>
<?php foreach($channel_entry_data['custom_fields'] as $custom_field_id => $custom_field_data) : ?>
				<custom_field <?php foreach($custom_field_data as $custom_field_data_key => $custom_field_data_value) : if("data" != $custom_field_data_key) : ?><?= $custom_field_data_key ?>="<?= $custom_field_data_value ?>" <?php endif; endforeach; ?>>
					<?= $custom_field_data['data']; ?>
				</custom_field>
<?php endforeach; ?>
			</entry>
<?php endif; endforeach; ?>
		</channel>
<?php endforeach; ?>
	</channels>

</generator_template>