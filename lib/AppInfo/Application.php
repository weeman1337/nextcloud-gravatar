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

namespace OCA\Gravatar\AppInfo;

use OCA\Gravatar\GlobalAvatar\GlobalAvatarService;
use OCA\Gravatar\GlobalAvatar\GravatarService;
use OCA\Gravatar\Handler\AskSyncUserAvatarHandler;
use OCA\Gravatar\Handler\DirectUpdateSyncUserAvatarHandler;
use OCA\Gravatar\Handler\SyncUserAvatarHandler;
use OCA\Gravatar\Hooks\UserSessionHook;
use OCA\Gravatar\Notification\Notifier;
use OCA\Gravatar\Settings\SecuritySettings;
use \OCP\AppFramework\App;

/**
 * The gravatar app.
 */
class Application extends App {

	const APP_ID = 'gravatar';
	const APP_NAME = 'Gravatar';

	/**
	 * Application constructor.
	 * Registers the app's services.
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams=array()){
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		$container->registerService(GlobalAvatarService::class, function() use ($server) {
			$httpClient = $server->getHTTPClientService()->newClient();
			return new GravatarService($httpClient);
		});

		$container->registerService(DirectUpdateSyncUserAvatarHandler::class, function() use ($server, $container) {
			$globalAvatarService = $container->query(GlobalAvatarService::class);
			$avatarManager = $server->getAvatarManager();
			return new DirectUpdateSyncUserAvatarHandler($globalAvatarService, $avatarManager);
		});

		$container->registerService(SyncUserAvatarHandler::class, function() use ($container, $server) {
			$config = $server->getConfig();
			$askUser = $config->getAppValue(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, 'no') === 'yes';

			if ($askUser === true) {
				$directUpdateSyncUserAvatarHandler = $container->query(DirectUpdateSyncUserAvatarHandler::class);
				$notificationManager = $server->getNotificationManager();
				$config = $server->getConfig();
				return new AskSyncUserAvatarHandler($notificationManager, $config, $directUpdateSyncUserAvatarHandler);
			} else {
				return $container->query(DirectUpdateSyncUserAvatarHandler::class);
			}
		});

		$container->registerService(UserSessionHook::class, function() use ($server, $container) {
			$userSession = $server->getUserSession();
			$syncUserAvatarHandler = $container->query(SyncUserAvatarHandler::class);
			return new UserSessionHook($userSession, $syncUserAvatarHandler);
		});

		$container->registerService(Notifier::class, function() use ($server) {
			$lFactory = $server->getL10NFactory();
			$urlGenerator = $server->getURLGenerator();
			return new Notifier($lFactory, $urlGenerator);
		});

		$notificationManager = $server->getNotificationManager();
		$notificationManager->registerNotifier(
			function() use ($container) {
				return $this->getContainer()->query(Notifier::class);
			},
			function () {
				return [
					'id' => Application::APP_ID,
					'name' => Application::APP_NAME,
				];
			}
		);
	}
}
