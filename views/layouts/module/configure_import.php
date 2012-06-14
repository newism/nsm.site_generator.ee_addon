<div class="tg">
    
    <h3>Structure</h3>

    <?php if(empty($config['channels'])) : ?>
        
        <div class="error alert">There are no channels defined in your bundle structure.xml</div>
        
    <?php else: ?>
        
        <div class="alert info">
            The following channels, categories, statuses, fields and entries will be imported. Your existing structure will not be modified, new elements will be added.
        </div>
            <ul class='menu tabs'>
            <?php foreach($config['channels'] as $channel) : ?>
                <li><a href='#channel-<?= $channel['channel_name'] ?>'><?= $channel['channel_title']; ?></a></li>
            <?php endforeach; ?>
            <li><a href="#channel-show_all"><?= lang("Show all"); ?></a></li>
            </ul>

            <?php foreach($config['channels'] as $channel_name => $channel) : ?>
            <div id="channel-<?= $channel_name ?>">
                <h4 style="background:#fff; border-top:3px double #849099; margin-top:-1px"><?= $channel['channel_title'] ?> <code>[<?= $channel['channel_name']; ?>]</code></h4>
                <table class="data">
                    <tbody>
                        <tr>
                            <th scope="row">Field Group</th>
                            <td>
                                <?php if(isset($config['field_groups'][$channel['field_group']])) : ?>
                                <?= $config['field_groups'][$channel['field_group']]['group_name'] ?>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Status Group</th>
                            <td>
                                <?php if(isset($config['status_groups'][$channel['status_group']])) : ?>
                                <?= $config['status_groups'][$channel['status_group']]['group_name'] ?>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                            $category_count = 0;
                            $categories = array();
                            if(false === empty($channel['cat_group'])) {
                                $categories = explode("|", $channel['cat_group']);
                                $category_count = count($categories);
                            }
                        ?>
                        <tr>
                            <th scope="row" rowspan="<?= $category_count ?>">Category Group(s)</th>
                        <?php if($category_count == 0) : ?>
                            <td>&mdash;</td>
                        </tr>
                        <?php else : ?>
                            <?php foreach($categories as $count => $category_group_id) : ?>
                                <?php if($count > 1) : ?>
                                    <tr>
                                <?php endif; ?>
                                        <td><?= $config['category_groups'][$category_group_id]['group_name'] ?></td>
                                    </tr>
                            <?php endforeach; ?>
            			<?php endif; ?>

                        <?php
                            $entry_count = count($channel['entries']);
                        ?>
                        <tr>
                            <th scope="row" rowspan="<?= $entry_count ?>">Entries</th>
                            <?php if($entry_count == 0) : ?>
                                <td></td>
                            </tr>
                            <?php else : ?>
                                <?php foreach($channel['entries'] as $count => $entry): ?>
                                    <?php if($count > 1) : ?>
                                        <tr>
                                    <?php endif; ?>
                                        <td><?= $entry['title']; ?></td>
                                        </tr>
                                <?php endforeach; ?>
            			    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        
    <?php endif; ?>


</div>

<div class="tg">

    <h3>Templates</h3>
    
    <?php if(empty($config["template_groups"])) : ?>
        <div class="alert error">
            No templates were found in the theme.
        </div>
    <?php else: ?>

        <div class="alert info">
            Your exising templates will be backed up then removed. The following templates will then be imported. A backup of your existing templates can be found in …
        </div>

        <ul class='menu tabs'>
        <?php $i=0; foreach($config['template_groups'] as $templateGroup) : $i++; ?>
            <li><a href='#template-<?= $i ?>'><?= $templateGroup['group_name']; ?></a></li>
        <?php endforeach; ?>
        <li><a href="#template-show_all"><?= lang("Show all"); ?></a></li>
        </ul>

        <?php $i=0; foreach($config['template_groups'] as $templateGroup) : $i++; ?>
        <div id="template-<?= $i; ?>">
            <h4 style="background:#fff; border-top:3px double #849099; margin-top:-1px"><?= $templateGroup['group_name']; ?></h4>
            <table class="data">
                <thead>
                     <tr>
                         <th scope="col">Title</th>
                         <th scope="col">Type</th>
                         <th scope="col">Notes</th>
                     </tr>
                 </thead>
                <tbody>
                    <?php foreach($templateGroup['templates'] as $template) : ?>
                    <tr>
                        <th scope="row"><?= $template['template_name']; ?></th>
                        <td><?= $template['template_type']; ?></td>
                        <td><?= $template['template_notes'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
</div>

<div class="tg">
    <h3>Global Variables</h3>
    <?php if(empty($config['global_variables'])) : ?>
    <div class="error alert">There are no global variables defined in your theme structure.xml</div>
    <?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($config['global_variables'] as $variable)?>
            <tr>
                <th scope="row"><?= $variable['variable_name']; ?></th>
                <td><pre style="white-space:pre-wrap; font-family:inherit;"><?= htmlentities($variable['variable_data']); ?></pre></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="tg">
    <h3>Snippets</h3>
    <?php if(empty($config['snippets'])) : ?>
    <div class="error alert">There are no snippets defined in your theme structure.xml</div>
    <?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($config['snippets'] as $snippet)?>
            <tr>
                <th scope="row"><?= $snippet['snippet_name']; ?></th>
                <td><pre style="white-space:pre-wrap; font-family:inherit;"><?= htmlentities($snippet['snippet_contents']); ?></pre></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="action" style="text-align:right">
	<input type="submit" class="submit" value="Begin Import" />
</div>