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

use OCA\Gravatar\AppInfo\Application;
use OCP\IConfig;
use OCP\IUser;
use OCP\Notification\IManager;

/**
 * This handler checks if the user has been asked whether he wants to use Gravatar.
 * If he hasn't been asked yet, it will create a notification.
 * If the user wants to use Gravatar it does a Gravatar update.
 */
class AskSyncUserAvatarHandler implements SyncUserAvatarHandler {
	/**
	 * @var IManager
	 */
	private $notificationManager;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var DirectUpdateSyncUserAvatarHandler
	 */
	private $directUpdateSyncUserAvatarHandler;

	/**
	 * AskSyncUserAvatarHandler constructor.
	 *
	 * @param IManager $notificationManager
	 * @param IConfig $config
	 * @param DirectUpdateSyncUserAvatarHandler $directUpdateSyncUserAvatarHandler
	 */
	public function __construct(IManager $notificationManager, IConfig $config, DirectUpdateSyncUserAvatarHandler $directUpdateSyncUserAvatarHandler) {
		$this->notificationManager = $notificationManager;
		$this->config = $config;
		$this->directUpdateSyncUserAvatarHandler = $directUpdateSyncUserAvatarHandler;
	}

	/**
	 * Checks if the user has been asked whether to use Gravatar or not.
	 * If he hasn't been asked yet, display a notification.
	 * Does an update if he wants to use Gravatar.
	 *
	 * @param IUser $user The user to sync the avatar for.
	 * @return void
	 */
	public function sync(IUser $user) {
		$userId = $user->getUID();
		$userAsked = $this->config->getUserValue($userId, Application::APP_ID, 'userAsked', 'no') !== 'no';

		if ($userAsked === false) {
			$this->createAskGravatarNotification($user);
			$this->config->setUserValue($userId, Application::APP_ID, 'userAsked', 'yes');
		} else {
			$useGravatar = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'useGravatar', 'no') !== 'no';
			if ($useGravatar === true) {
				$this->directUpdateSyncUserAvatarHandler->sync($user);
			}
		}
	}

	/**
	 * Creates a notification to ask the user whether he wants to use Gravatar.
	 *
	 * @param IUser $user
	 * @return void
	 */
	private function createAskGravatarNotification(IUser $user) {
		$notification = $this->notificationManager->createNotification();

		$acceptAction = $notification->createAction();
		$acceptAction->setLabel('confirm')
			->setLink('/apps/gravatar/settings/useGravatar/enable', 'GET');

		$declineAction = $notification->createAction();
		$declineAction->setLabel('decline')
			->setLink('/apps/gravatar/settings/useGravatar/disable', 'GET');

		$notification->setApp(Application::APP_ID)
			->setUser($user->getUID())
			->setDateTime(new \DateTime())
			->setObject('useGravatar', $user->getUID())
			->setSubject('useGravatar')
			->addAction($acceptAction)
			->addAction($declineAction);

		$this->notificationManager->notify($notification);
	}
}
