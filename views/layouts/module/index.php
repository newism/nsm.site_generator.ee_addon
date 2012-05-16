<div class="tg">
	<h2>Site bundles</h2>
	<div class="alert info">Choose a generator bundle from the options below. Each generator may require specific extensions, modules or plugins; be sure to check the specific requirements.</div>
	<table>
		<thead>
			<tr>
				<th scope="col">Bundle Title</th>
				<th scope="col">Description</th>
				<th scope="col">Author</th>
				<th scope="col">Version</th>
				<th scope="col">Documentation</th>
                <th scope="col">Required Addons</th>
                <th scope="col"></th>
			</tr>
		</thead>
		<tbody>
			<?php if ($error) : ?>
				<tr><td class="alert error" colspan="7"><?= $error ?></td></tr>
			<?php endif; ?>
			<?php if ($generators) : ?>
				<?php foreach ($generators as $count => $generator) : ?>
				<tr>
					<th scope="row" style="width:auto">
						<a href="<?= $generator["generator_url"] ?>"><?= $generator["title"] ?></a>
					</th>
					<td><?= $generator->description; ?></td>

					<td style="white-space: nowrap">
						<?php $authors = ''; 
							foreach ($generator->authors->author as $author)
								$authors[] = ' <a href='. $author['url'] .'>'. $author['name'] .'</a>';
							print(implode(", ",$authors));
						?>
					</td>
					<td style="white-space: nowrap"><?= $generator["version"] ?></td>
					<td><a href="<?= $generator["download_url"] ?>">Documentation</a></td>
                    <td style="white-space: nowrap">
                        <?php if ($required_addons = $generator->xpath("requirements/requirement[@type='addon']")) : ?>
                        <?php foreach ($required_addons as $addon): ?>
                            <a href="<?= $addon["download_url"] ?>"><?= $addon["title"]; ?> <?= $addon["version"]; ?></a><br />
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </td>
                    <td><a href="<?= $generator["generator_url"] ?>" class="btn">Configure</a></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>