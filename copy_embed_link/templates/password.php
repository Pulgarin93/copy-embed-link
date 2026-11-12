<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Password gate for a protected public link.
 *
 * @var array $_
 */
?>
<div class="cel-guest-wrap">
	<div class="cel-guest-box">
		<h2>This link is protected</h2>
		<p>Enter the password the owner gave you to open this file.</p>
		<?php if (!empty($_['error'])) { ?>
			<p class="cel-error">That password is not correct. Please try again.</p>
		<?php } ?>
		<form method="post" action="<?php p($_['action']); ?>">
			<input type="hidden" name="redirect" value="<?php p($_['redirect']); ?>">
			<label for="cel-password">Password</label>
			<input type="password" id="cel-password" name="password" required autofocus autocomplete="current-password">
			<button type="submit" class="primary">Open file</button>
		</form>
	</div>
</div>
<style>
	.cel-guest-wrap { display: flex; justify-content: center; padding: 48px 16px; }
	.cel-guest-box { max-width: 420px; width: 100%; }
	.cel-guest-box form { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
	.cel-guest-box .cel-error { color: var(--color-error, #c40f14); }
</style>
