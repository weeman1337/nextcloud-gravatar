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

use OCA\Gravatar\AppInfo\Application;
use OCA\Gravatar\Controller\SettingsController;
use OCA\Gravatar\Settings\SecuritySettings;
use OCP\IConfig;
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
	 * @var SettingsController
	 */
	private $settingsController;

	/**
	 * Setups objects for tests.
	 *
	 * @before
	 * @return void
	 */
	public function setupTestObjects() {
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->settingsController = new SettingsController($this->config);
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
			->with(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, true);
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
			->with(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, false);
		$response = $this->settingsController->disableAskUserSetting();
		self::assertEquals(['askUser' => false,], $response->getData());
	}
}
