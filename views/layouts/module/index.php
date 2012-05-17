<div class="tg">
	<h2>Site bundles</h2>
	<div class="alert info">Choose a site structure bundle from the options below. Each bundle may require specific extensions, modules or plugins post import; be sure to check the specific requirements.</div>
	<table>
		<thead>
			<tr>
				<th scope="col">Bundle Title</th>
				<th scope="col">Version</th>
				<th scope="col">Description</th>
				<th scope="col">Author</th>
				<th scope="col">Documentation</th>
                <th scope="col">Required Addons</th>
                <th scope="col"></th>
			</tr>
		</thead>
		<tbody>
			<?php if($error) : ?>
				<tr><td class="alert error" colspan="6"><?= $error ?></td></tr>
			<?php endif; ?>
			<?php if($generators) : ?>
				<?php foreach ($generators as $count => $generator) : ?>
				<tr>
					<th scope="row" style="width:auto">
						<a href="<?= $generator["generator_url"] ?>"><?= $generator->title ?></a>
					</th>
					<td style="white-space: nowrap"><?= $generator->version ?></td>
					<td><?= $generator->description; ?></td>
					<td style="white-space: nowrap">
						<?php 
						    $authors = array(); 
							foreach ($generator->authors->author as $author) {
								$authors[] = '<a href='. $author['url'] .'>'. $author['name'] .'</a>';
							}
							print(implode("<br />",$authors));
						?>
					</td>
					<td><a href="<?= $generator->download_url ?>">Documentation</a></td>
                    <td>
                        <?php 
                            $addons = array();
                            foreach ($generator->xpath("requirements/requirement[@type='addon']") as $required_addon) {
                                $addons[] = '<a href="'. $required_addon["url"].'">'.$required_addon["name"].' '. $required_addon["version"].'</a>';
                            }
                            print(implode("<br />",$addons));
                        ?>
                    </td>
                    <td><a href="<?= $generator["generator_url"] ?>" class="btn">Configure Import</a></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>