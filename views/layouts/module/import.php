<div class="tg">
<div class="alert info">Import complete. Here's the log:</div>
<?php foreach($log as $log_item): ?>
	<?php if($log_item['type'] == "title") : ?>
	<h2><?= $log_item["text"]; ?></h2>
	<?php else : ?>
	<div class="alert <?= $log_item['type']; ?>" style="padding-left:27px"><?= $log_item["text"]; ?></div>
	<?php endif; ?>
<?php endforeach; ?>
</div>