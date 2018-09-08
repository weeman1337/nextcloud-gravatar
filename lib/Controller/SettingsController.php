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

namespace OCA\Gravatar\Controller;

use OCA\Gravatar\AppInfo\Application;
use OCA\Gravatar\Handler\DirectUpdateSyncUserAvatarHandler;
use OCA\Gravatar\Settings\SecuritySettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Notification\IManager;

/**
 * This controller handles app settings requests.
 */
class SettingsController extends Controller {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var IManager
	 */
	private $notificationManager;

	/**
	 * @var DirectUpdateSyncUserAvatarHandler
	 */
	private $directUpdateSyncUserAvatarHandler;

	/**
	 * SettingsController constructor.
	 *
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param IManager $notificationManager
	 * @param DirectUpdateSyncUserAvatarHandler $directUpdateSyncUserAvatarHandler
	 */
	public function __construct(
		IConfig $config,
		IUserSession $userSession,
		IManager $notificationManager,
		DirectUpdateSyncUserAvatarHandler $directUpdateSyncUserAvatarHandler
	) {
		$this->config = $config;
		$this->userSession = $userSession;
		$this->notificationManager = $notificationManager;
		$this->directUpdateSyncUserAvatarHandler = $directUpdateSyncUserAvatarHandler;
	}

	/**
	 * Enables the ask user setting.
	 *
	 * @return JSONResponse
	 */
	public function enableAskUserSetting(): JSONResponse {
		return $this->setAskUserSetting(true);
	}

	/**
	 * Disables the ask user setting.
	 *
	 * @return JSONResponse
	 */
	public function disableAskUserSetting(): JSONResponse {
		return $this->setAskUserSetting(false);
	}

	/**
	 * Sets the ask user setting.
	 *
	 * @param bool $enabled
	 * @return JSONResponse
	 */
	private function setAskUserSetting(bool $enabled): JSONResponse {
		$this->config->setAppValue(
			Application::APP_ID,
			SecuritySettings::SETTING_ASK_USER,
			$enabled ? 'yes' : 'no'
		);
		return new JSONResponse(['askUser' => $enabled,]);
	}

	/**
	 * Sets the user gravatar setting to enabled and does a one time gravatar sync.
	 *
	 * @return JSONResponse
	 */
	public function enableUserGravatar(): JSONResponse {
		$user = $this->userSession->getUser();
		$this->directUpdateSyncUserAvatarHandler->sync($user);
		return $this->setUserUseGravatar('yes');
	}

	/**
	 * Sets the user gravatar setting to disabled.
	 *
	 * @return JSONResponse
	 */
	public function disableUserGravatar(): JSONResponse {
		return $this->setUserUseGravatar('no');
	}

	/**
	 * Sets the user setting "useGravatar".
	 * Also marks "useGravatar" notifications as processed.
	 *
	 * @param string $useGravatar
	 * @return JSONResponse
	 * @throws \OCP\PreConditionNotMetException
	 */
	private function setUserUseGravatar(string $useGravatar): JSONResponse {
		$user = $this->userSession->getUser();
		$userId = $user->getUID();

		$this->config->setUserValue($userId, Application::APP_ID, 'useGravatar', $useGravatar);

		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_ID)
			->setUser($userId)
			->setObject('useGravatar', $userId);
		$this->notificationManager->markProcessed($notification);

		return new JSONResponse();
	}
}
