<div class="tg">
	<h2>Site templates</h2>
	<div class="alert info">Choose a generator template from the options below. Each generator may require specific extensions, modules or plugins; be sure to check the specific requirements.</div>
	<table>
		<thead>
			<tr>
				<th scope="col">Title</th>
				<th scope="col">Description</th>
				<th scope="col" style="width:150px">Required Addons</th>
				<th scope="col">Author</th>
				<th scope="col">Version</th>
				<th scope="col">Documentation</th>
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
						<a href="<?= $generator["generator_url"] ?>" style="font-size:14px"><?= $generator["title"] ?></a>
					</th>
					<td><?= $generator->description; ?></td>
					<td>
						<?php if($required_addons = $generator->xpath("requirements/requirement[@type='addon']")) : ?>
							<?php foreach ($required_addons as $addon): ?>
							<a href="<?= $addon["download_url"] ?>"><?= $addon["title"]; ?> <!-- v<?= $addon["version"]; ?> --></a><br />
							<?php endforeach; ?>
						<?php endif; ?>
					
					</td>
					<td>
						<?php $authors = ''; 
							foreach ($generator->authors->author as $author)
								$authors[] = ' <a href='. $author['url'] .'>'. $author['name'] .'</a>';
							print(implode(", ",$authors));
						?>
					</td>
					<td><?= $generator["version"] ?></td>
					<td><a href="<?= $generator["download_url"] ?>">Documentation</a></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>