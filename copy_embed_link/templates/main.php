<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Management panel shell. The rows are rendered by
 * js/copy_embed_link-panel.js from the app's JSON API.
 *
 * @var \OCP\IL10N $l
 * @var array $_
 */
?>
<div id="app-content">
	<div id="app-content-wrapper">
		<div class="section" id="copy-embed-link-panel">
			<h2><?php p($l->t('Embed links')); ?></h2>
			<p class="settings-hint">
				<?php p($l->t('Every embed and share link you created. Copy one again, or revoke it and it stops working everywhere it was pasted.')); ?>
			</p>
			<table class="grid" id="cel-links-table">
				<thead>
					<tr>
						<th><?php p($l->t('Type')); ?></th>
						<th><?php p($l->t('File')); ?></th>
						<th><?php p($l->t('Link')); ?></th>
						<th><?php p($l->t('Expires')); ?></th>
						<th><?php p($l->t('Download')); ?></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody id="cel-links-body"></tbody>
			</table>
			<p id="cel-empty" hidden>
				<?php p($l->t('You have not created any links yet. In Files, right-click a file and choose “Copy embed link”.')); ?>
			</p>
			<p id="cel-loading"><?php p($l->t('Loading your links…')); ?></p>
			<p id="cel-error" class="msg error" hidden></p>
		</div>
	</div>
</div>
