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

script(\OCA\Gravatar\AppInfo\Application::APP_ID, 'settings/personal');

?>

<div id="gravatar" class="section">
	<h2>
		<?php p(\OCA\Gravatar\AppInfo\Application::APP_NAME); ?>
		<img id="use-gravatar-loading" class="inlineblock" style="display: none;" src="/core/img/loading-small.gif">
	</h2>
	<p class="settings-hint">
		<?php p($l->t('For Gravatar to work it sends a hashed version of your email address to Gravatar.
		If you don\'t want this you can disable Gravatar here.')); ?>
	</p>
	<p>
		<input
			id="use-gravatar"
			name="use-gravatar"
			type="checkbox"
			class="checkbox"
			value="1"
			<?php if ($_['useGravatar']): ?> checked="checked"<?php endif; ?>>
		<label for="use-gravatar"><?php p($l->t('Use Gravatar for loading your avatar')); ?></label>
	</p>
</div>
