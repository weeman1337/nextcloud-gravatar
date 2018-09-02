<?php
declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Michael Weimann <mail@michael-weimann.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

script(\OCA\Gravatar\AppInfo\Application::APP_ID, 'settings/security');

?>

<div id="gravatar" class="section">
	<h2>
		<?php p(\OCA\Gravatar\AppInfo\Application::APP_NAME); ?>
		<img id="ask-user-loading" class="inlineblock" style="display: none;" src="/core/img/loading-small.gif">
	</h2>
	<p class="settings-hint">
		<?php p($l->t('For Gravatar to work it sends a hashed version of the users\' email addresses to Gravatar.
		Some users may not feel comfortable with that.')); ?>
	</p>
	<p>
		<input
			id="ask-user"
			name="ask-user"
			type="checkbox"
			class="checkbox"
			value="1"
			<?php if ($_['askUser']): ?> checked="checked"<?php endif; ?>>
		<label for="ask-user"><?php p($l->t('Ask every user if he wants to use Gravatar.')); ?></label>
	</p>
</div>
