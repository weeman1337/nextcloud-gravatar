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

namespace OCA\Gravatar\Hooks;

use OCA\Gravatar\Handler\SyncUserAvatarHandler;
use OCP\IUserSession;

/**
 * This class handles user session hooks.
 */
class UserSessionHook {
	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var SyncUserAvatarHandler
	 */
	private $syncUserAvatarHandler;

	/**
	 * UserSessionHook constructor.
	 *
	 * @param IUserSession $userSession
	 * @param SyncUserAvatarHandler $syncUserAvatarHandler
	 */
	public function __construct(IUserSession $userSession, SyncUserAvatarHandler $syncUserAvatarHandler) {
		$this->userSession = $userSession;
		$this->syncUserAvatarHandler = $syncUserAvatarHandler;
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function register() {
		$this->userSession->listen('\OC\User', 'postLogin', [$this, 'onPostLogin']);
	}

	/**
	 * Syncs the user avatar on login.
	 *
	 * @return void
	 */
	public function onPostLogin() {
		$user = $this->userSession->getUser();
		$this->syncUserAvatarHandler->sync($user);
	}
}
