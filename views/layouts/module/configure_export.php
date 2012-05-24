<?php
    $EE =& get_instance();
?>

<div class="tg" id="channels">
    <h2>Bundle Details</h2>
    <table class="data">
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
    <div class="alert info">Exporting channels also exports its related custom fields, statuses &amp; categories. Channel entries are optional.</div>

    <ul class="menu tabs">
        <?php foreach ($channels as $count => $channel) : ?>
            <li><a href="#channel_prefs-<?= $channel['channel_id'] ?>"><?=  $channel['channel_title'] ?></a></li>
        <?php endforeach; ?>
        <li><a href="#channel_prefs-show_all"><?= lang("Show all"); ?></a></li>
    </ul>

    <?php foreach ($channels as $count => $channel) : ?>

    <div id="channel_prefs-<?= $channel['channel_id'] ?>">
    <h4 style="background:#fff; border-top:3px double #849099; margin-top:-1px"><?= $channel['channel_title'] ?> <code>[<?= $channel['channel_name']; ?>]</code></h4>
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Attribute</th>
                <th scope="col" style="width:20px">ID</th>
                <th scope="col">Title</th>
                <th scope="col" style="width:40px; text-align:right">Export</th>
            </tr>
        </thead>
        <tbody>
            <tr class="odd">
                <th scope="row">Channel:</th>
                <td style="text-align:right"><?= $channel['channel_id'] ?></td>
                <td><?= $channel['channel_title'] ?></td>

                <?php 
                    $cat_count = count($channel['cat_group']);
                    $rowspan = ($cat_count) ? $cat_count : 1; 
                    $rowspan += 3;
                ?>
                <td rowspan="<?= $rowspan ?>" style="text-align:right; vertical-align:top;">
                    <?=
                        $EE->nsm_site_generator_helper->checkbox(
                            $input_prefix.'[channels]['.$channel['channel_id'].'][enabled]',
                            true,
                            $data['channels'][$channel['channel_id']]['enabled']
                        );
                    ?>
                </td>
            </tr>

            <?php if(false == empty($channel['field_group'])) : ?>
            <tr class="even">
                <th scope="row">Field Group:</th>
                <td style="text-align:right"><?= $channel['field_group']['group_id']; ?></td>
                <td><?= $channel['field_group']['group_name']; ?></td>
            </tr>
            <?php else: ?>
            <tr class="even">
                <th scope="row">Field Group:</th>
                <td colspan="2">&mdash;</td>
            </tr>
            <?php endif; ?>

            <?php if(false == empty($channel['status_group'])) : ?>
            <tr class="odd">
                <th scope="row">Status Group:</th>
                <td style="text-align:right"><?= $channel['status_group']['group_id']; ?></td>
                <td><?= $channel['status_group']['group_name']; ?></td>
            </tr>
            <?php else: ?>
            <tr class="odd">
                <th scope="row">Status Group:</th>
                <td colspan="2">&mdash;</td>
            </tr>
            <?php endif; ?>

            <?php if(empty($channel['cat_group'])) : ?>
                <tr class="even">
                    <th scope="row">Category Groups:</th>
                    <td colspan="2">&mdash;</td>
                </tr>
            <?php else: ?>
                <?php $i = 0; foreach($channel['cat_group'] as $count => $category_group) : $i++; ?>
                <tr class="even">
                    <?php if($i == 1) : ?>
                    <th scope="row" rowspan="<?= count($channel['cat_group']); ?>" style="vertical-align:top">Category Groups:</th>
                    <?php endif; ?>
                    <td style="text-align:right"><?= $category_group['group_id']; ?></td>
                    <td><?= $category_group['group_name']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php if(empty($channel['entries'])) : ?>
                <tr class="even">
                    <th scope="row" style="vertical-align:top">Channel Entries:</th>
                    <td colspan="3">&mdash;</td>
                </tr>
            <?php else: ?>
                <?php $i = 0; foreach($channel['entries'] as $count => $entry) : $i++; ?>
                <tr class="even">
                    <?php if($i == 1) : ?>
                    <th scope="row" rowspan="<?= count($channel['entries']); ?>" style="vertical-align:top">Channel Entries:</th>
                    <?php endif; ?>
                    <td style="text-align:right"><?= $entry['entry_id']; ?></td>
                    <td><?= $entry['title']; ?> (<?= $entry['url_title']; ?>)</td>
                    <td style="text-align:right; vertical-align:top;">
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

<div class="tg" id="template-groups">
    <h2>Which templates would you like to export?</h2>
    <div class="alert info">Check any of the options to export templates. <strong>Template data will be pulled from the DB so make sure your templates are synced</strong>.</div>

    <ul class="menu tabs">
        <?php foreach ($template_groups as $count => $templateGroup) : ?>
            <li><a href="#template-groups-<?= $templateGroup['group_id'] ?>"><?=  $templateGroup['group_name'] ?></a></li>
        <?php endforeach; ?>
        <li><a href="#channel_prefs-show_all"><?= lang("Show all"); ?></a></li>
    </ul>

    <?php foreach ($template_groups as $count => $templateGroup) : ?>
    <div id="template-groups-<?= $templateGroup['group_id'] ?>">
        <h4 style="background:#fff; border-top:3px double #849099; margin-top:-1px"><?=  $templateGroup['group_name'] ?></h4>
        <?php if(empty($templateGroup['templates'])): ?>
            <div class="alert error">This template group has no templates</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th scope="col" style="width:20px">ID</th>
                    <th scope="col">Title</th>
                    <th scope="col">Type</th>
                    <th scope="col">Notes</th>
                    <th scope="col" style="width:70px;">
                        <input type="checkbox" style="float:right" />
                        Export
                    </th>
                </tr>
            </thead>
            <tbody>
                    <?php foreach($templateGroup['templates'] as $template) : ?>
                        <tr>
                            <td><?= $template['template_id']; ?></td>
                            <th scope="row"><?= $template['template_name']; ?></th>
                            <td><?= $template['template_type']; ?></td>
                            <td><?= $template['template_notes'] ?></td>
                            <td style="text-align:right; vertical-align:top;">
                                <?=
                                    $EE->nsm_site_generator_helper->checkbox(
                                        $input_prefix.'[template_groups]['.$count.'][templates]['.$template['template_id'].']',
                                        $template['template_id'],
                                        in_array($template['template_id'], $data['template_groups'][$count]['templates'])
                                    );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="action" style="text-align:right">
    <input type="submit" class="submit" value="Begin Export" />
</div>