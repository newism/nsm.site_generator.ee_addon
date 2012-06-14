<div class="tg">
	<h2>Themes</h2>
	<div class="alert info">Choose a theme from the options below. Themes may require specific extensions, modules or plugins post import; be sure to check the specific requirements.</div>
	<table>
		<thead>
			<tr>
				<th scope="col">Title</th>
				<th scope="col">Version</th>
				<th scope="col">Description</th>
				<th scope="col" style="min-width:100px;">Author</th>
				<th scope="col" style="min-width:100px;">Documentation</th>
                <th scope="col" style="min-width:150px;">Required Addons</th>
                <th scope="col" style="min-width:120px;"></th>
			</tr>
		</thead>
		<tbody>
			<?php if($error) : ?>
				<tr><td class="alert error" colspan="7"><?= $error ?></td></tr>
			<?php endif; ?>
			<?php if($themes) : ?>
				<?php foreach ($themes as $count => $theme) : ?>
				<tr>
					<th scope="row" style="width:auto">
						<a href="<?= $theme["theme_url"] ?>"><?= $theme->title ?></a>
					</th>
					<td style="white-space: nowrap"><?= $theme->version ?></td>
					<td><?= $theme->description; ?></td>
					<td style="white-space: nowrap">
						<?php 
						    $authors = array(); 
							foreach ($theme->authors->author as $author) {
								$authors[] = '<a href='. $author['url'] .'>'. $author['name'] .'</a>';
							}
							print(implode("<br />",$authors));
						?>
					</td>
					<td><a href="<?= $theme->downloadUrl ?>">Documentation</a></td>
                    <td>
                        <?php 
                            $addons = array();
                            foreach ($theme->xpath("requirements/requirement[@type='addon']") as $required_addon) {
                                $addons[] = '<a href="'. $required_addon["url"].'">'.$required_addon["name"].' '. $required_addon["version"].'</a>';
                            }
                            print(implode("<br />",$addons));
                        ?>
                    </td>
                    <td><a href="<?= $theme["theme_url"] ?>" class="btn">Preview Import</a></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>