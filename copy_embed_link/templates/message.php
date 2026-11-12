<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Plain message page for public links (expired, not found, ...).
 *
 * @var array $_
 */
?>
<div class="cel-guest-wrap">
	<div class="cel-guest-box">
		<h2><?php p($_['title']); ?></h2>
		<p><?php p($_['message']); ?></p>
	</div>
</div>
<style>
	.cel-guest-wrap { display: flex; justify-content: center; padding: 48px 16px; }
	.cel-guest-box { max-width: 420px; width: 100%; }
</style>
