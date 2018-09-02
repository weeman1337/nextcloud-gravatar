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

namespace OCA\Gravatar\Tests\Unit\Controller;

use OCA\DAV\Db\Direct;
use OCA\Gravatar\AppInfo\Application;
use OCA\Gravatar\Controller\SettingsController;
use OCA\Gravatar\Handler\DirectUpdateSyncUserAvatarHandler;
use OCA\Gravatar\Settings\SecuritySettings;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * This class provides test cases for the settings controller.
 */
class SettingsControllerTest extends TestCase {
	/**
	 * @var IConfig|MockObject
	 */
	private $config;

	/**
	 * @var DirectUpdateSyncUserAvatarHandler|MockObject
	 */
	private $directUpdateSyncUserAvatarHandler;

	/**
	 * @var IUserSession|MockObject
	 */
	private $userSession;

	/**
	 * @var SettingsController
	 */
	private $settingsController;

	/**
	 * @var IUser|MockObject
	 */
	private $user;

	/**
	 * @var IManager|MockObject
	 */
	private $notificationManager;

	/**
	 * @var INotification|MockObject
	 */
	private $notification;

	/**
	 * Setups objects for tests.
	 *
	 * @before
	 * @return void
	 */
	public function setupTestObjects() {
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->directUpdateSyncUserAvatarHandler = $this
			->getMockBuilder(DirectUpdateSyncUserAvatarHandler::class)
			->disableOriginalConstructor()
			->getMock();
		$this->user = $this->getMockBuilder(IUser::class) ->getMock();
		$this->user->method('getUID')->willReturn('user1');
		$this->userSession = $this->getMockBuilder(IUserSession::class)->getMock();
		$this->notificationManager = $this->getMockBuilder(IManager::class)->getMock();
		$this->notification = $this->getMockBuilder(INotification::class)->getMock();
		$this->settingsController = new SettingsController(
			$this->config,
			$this->userSession,
			$this->notificationManager,
			$this->directUpdateSyncUserAvatarHandler
		);
	}

	/**
	 * Checks that disabling the user gravatar setting works and
	 * the referenced notifications are dismissed.
	 *
	 * @test
	 * @return void
	 */
	public function testDisableUserGravatarSetting() {
		$this->userSession->method('getUser')
			->willReturn($this->user);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user1', Application::APP_ID, 'useGravatar', 'false');
		$this->expectMarkUseGravatarNotificationProcessed();
		$this->settingsController->disableUserGravatar();
	}

	/**
	 * Checks that enabling the user gravatar setting works,
	 * the referenced notifications are dismissed and
	 * a one time avatar sync is triggered.
	 *
	 * @test
	 * @return void
	 */
	public function testEnableUserGravatarSetting() {
		$this->userSession->method('getUser')
			->willReturn($this->user);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user1', Application::APP_ID, 'useGravatar', 'true');
		$this->expectMarkUseGravatarNotificationProcessed();
		$this->directUpdateSyncUserAvatarHandler->expects($this->once())
			->method('sync')
			->with($this->user);
		$this->settingsController->enableUserGravatar();
	}

	/**
	 * Expects the use gravatar notification to set as processed.
	 *
	 * @return void
	 */
	private function expectMarkUseGravatarNotificationProcessed() {
		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($this->notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($this->notification);
		$this->notification->expects($this->once())
			->method('setApp')
			->with(Application::APP_ID)
			->willReturnSelf();
		$this->notification->expects($this->once())
			->method('setUser')
			->with('user1')
			->willReturnSelf();
		$this->notification->expects($this->once())
			->method('setObject')
			->with('useGravatar', 'user1')
			->willReturnSelf();
	}

	/**
	 * Checks that the enable handler sets the "askUser" setting to true.
	 *
	 * @test
	 * @return void
	 */
	public function testEnableAskUserSetting() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, 'true');
		$response = $this->settingsController->enableAskUserSetting();
		self::assertEquals(['askUser' => true,], $response->getData());
	}

	/**
	 * Checks that the enable handler sets the "askUser" setting to false.
	 *
	 * @test
	 * @return void
	 */
	public function testDisableAskUserSetting() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, 'false');
		$response = $this->settingsController->disableAskUserSetting();
		self::assertEquals(['askUser' => false,], $response->getData());
	}
}
