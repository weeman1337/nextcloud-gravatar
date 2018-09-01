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

namespace OCA\Gravatar\Handler;

use OCA\Gravatar\GlobalAvatar\GlobalAvatarService;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\IImage;
use OCP\IUser;

/**
 * Checks whether there is an global avatar for the current user and
 * updates the avatar directly.
 */
class DirectUpdateSyncUserAvatarHandler implements SyncUserAvatarHandler {
	/**
	 * @var GlobalAvatarService
	 */
	private $globalAvatarService;

	/**
	 * @var IAvatarManager
	 */
	private $avatarManager;

	/**
	 * DirectUpdateSyncUserAvatarHandler constructor.
	 *
	 * @param GlobalAvatarService $globalAvatarService
	 * @param IAvatarManager $avatarManager
	 */
	public function __construct(GlobalAvatarService $globalAvatarService, IAvatarManager $avatarManager) {
		$this->globalAvatarService = $globalAvatarService;
		$this->avatarManager = $avatarManager;
	}

	/**
	 * Queries the global avatar.
	 * If there is one updates the user's avatar.
	 *
	 * @param IUser $user The user to check the global avatar for.
	 * @return void
	 */
	public function sync(IUser $user) {
		$avatar = $this->globalAvatarService->query($user);
		if ($avatar !== null) {
			$this->storeUserAvatar($user, $avatar);
		}
	}

	/**
	 * Stores the avatar of an user.
	 *
	 * @param IUser $user The user
	 * @param IImage $avatar The avatar to set
	 * @return void
	 */
	private function storeUserAvatar(IUser $user, IImage $avatar) {
		try {
			$userAvatar = $this->avatarManager->getAvatar($user->getUID());
			$userAvatar->set($avatar);
		} catch (NotFoundException $ignore) {
		} catch (\Exception $ignore) {}
	}
}
