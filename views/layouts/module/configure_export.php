<?php
    $EE =& get_instance();
?>

<div class="tg" id="channels">
    <h2>Bundle Details</h2>
    <table class="data NSM_Stripeable">
        <tr>
            <th scope="row">Title</th>
            <td><input
                type="text"
                value="<?= form_prep($data['title']) ?>"
                name="<?=$input_prefix?>[title]"
            /></td>
        </tr>
        <tr>
            <th scope="row">Description</th>
            <td><textarea
                type="text"
                name="<?=$input_prefix?>[description]"
            ><?= form_prep($data['description']) ?></textarea></td>
        </tr>
        <tr>
            <th scope="row">Version</th>
            <td><input
                type="text"
                value="<?= form_prep($data['version']) ?>"
                name="<?=$input_prefix?>[version]"
            /></td>
        </tr>
        <tr>
            <th scope="row">Download URL</th>
            <td><input
                type="text"
                value="<?= form_prep($data['download_url']) ?>"
                name="<?=$input_prefix?>[download_url]"
            /></td>
        </tr>
        <tr>
            <th scope="row">Post Import Instructions</th>
            <td><textarea
                type="text"
                name="<?=$input_prefix?>[post_import_instructions]"
            ><?= form_prep($data['post_import_instructions']) ?></textarea></td>
        </tr>
    </table>
</div>

<div class="tg" id="channels">
    <h2>Which channels would you like to export?</h2>
    <div class="alert info">Check any of the options below to import the channel, it's related categories, statuses and custom fields as defined in the XML config.</div>

    <ul class="menu tabs">
        <?php foreach ($channels as $count => $channel) : ?>
            <li><a href="#channel_prefs-<?= $channel['channel_id'] ?>"><?=  $channel['channel_title'] ?></a></li>
        <?php endforeach; ?>
        <li><a href="#channel_prefs-show_all"><?= lang("Show all"); ?></a></li>
    </ul>

    <?php foreach ($channels as $count => $channel) : ?>
    <div id="channel_prefs-<?= $channel['channel_id'] ?>">
    <table class="">
        <thead>
            <tr>
                <th scope="col">Attribute</th>
                <th scope="col" style="width:20px">ID</th>
                <th scope="col">Title</th>
                <th scope="col" style="width:70px">Export <input type="checkbox" style="float:right" /></th>
            </tr>
        </thead>
        <tbody>
            <tr class="odd">
                <th scope="row">Channel Title:</th>
                <td style="text-align:right"><?= $channel['channel_id'] ?></td>
                <td><?= $channel['channel_title'] ?></td>
                <td rowspan="<?= count($channel['cat_group'])+3 ?>" style="text-align:right; vertical-align:top">
                    <?=
                        $EE->nsm_site_generator_helper->checkbox(
                            $input_prefix.'[channels]['.$channel['channel_id'].'][enabled]',
                            true,
                            array_key_exists($channel['channel_id'], $data['channels']),
                            array('generate_shadow' => true)
                        );
                    ?>
                </td>
            </tr>
            <tr class="even">
                <th scope="row">Field Group:</th>
                <td style="text-align:right"><?= print($channel['field_group_id']); ?></td>
                <td><?= print($channel['field_group_name']); ?></td>
            </tr>
            <tr class="odd">
                <th scope="row">Status Group:</th>
                <td style="text-align:right"><?= $channel['status_group_id']; ?></td>
                <td><?= $channel['status_group_name']; ?></td>
            </tr>

            <?php if(empty($channel['cat_group'])) : ?>
                <tr class="even alert error">
                    <th scope="row">Category Groups:</th>
                    <td colspan="2">No category groups assigned to this channel.</td>
                </tr>
            <?php else: ?>
                <?php $i = 0; foreach($channel['cat_group'] as $count => $category_group) : $i++; ?>
                <tr class="even">
                    <?php if($i == 1) : ?>
                    <th scope="row" rowspan="<?= count($channel['cat_group']); ?>">Category Groups:</th>
                    <?php endif; ?>
                    <td style="text-align:right"><?= $category_group['group_id']; ?></td>
                    <td><?= $category_group['group_name']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php if(empty($channel['entries'])) : ?>
                <tr class="even alert error">
                    <th scope="row">Channel Entries:</th>
                    <td colspan="2">No channel entries assigned to this channel.</td>
                </tr>
            <?php else: ?>
                <?php $i = 0; foreach($channel['entries'] as $count => $entry) : $i++; ?>
                <tr class="even">
                    <?php if($i == 1) : ?>
                    <th scope="row" rowspan="<?= count($channel['entries']); ?>">Channel Entries:</th>
                    <?php endif; ?>
                    <td style="text-align:right"><?= $entry['entry_id']; ?></td>
                    <td><?= $entry['title']; ?> (<?= $entry['url_title']; ?>)</td>
                    <td style="text-align:right; vertical-align:top">
                        <?=
                            $EE->nsm_site_generator_helper->checkbox(
                                $input_prefix.'[channels]['.$channel['channel_id'].'][entries][]',
                                $entry['entry_id'],
                                in_array($entry['entry_id'], $data['channels'][$channel['channel_id']]['entries'])
                            );
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php endforeach; ?>
</div>

<div class="action" style="text-align:right">
    <input type="submit" class="submit" value="Begin Export" />
</div>