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

namespace OCA\Gravatar\Tests\Settings;

use OCA\Gravatar\AppInfo\Application;
use OCA\Gravatar\Settings\SecuritySettings;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * This class provides test cases for the security settings handler.
 */
class SecuritySettingsTest extends TestCase {
	/**
	 * @var IConfig|MockObject
	 */
	private $config;

	/**
	 * @var SecuritySettings
	 */
	private $securitySettings;

	/**
	 * Setups test objects.
	 *
	 * @before
	 * @return void
	 */
	public function setupTestObjects() {
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->securitySettings = new SecuritySettings($this->config);
	}

	/**
	 * Checks that the app places its settings in "security".
	 *
	 * @test
	 * @return void
	 */
	public function testSection() {
		self::assertEquals('security', $this->securitySettings->getSection());
	}

	/**
	 * Checks that the gravatar settings are placed at the bottom (high priority value).
	 *
	 * @test
	 * @return void
	 */
	public function testPriority() {
		self::assertEquals(100, $this->securitySettings->getPriority());
	}

	/**
	 * Checks that the correct TemplateResponse is returned.
	 *
	 * @test
	 * @return void
	 */
	public function testGetForm() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, false)
			->willReturn('askUserTestValue');
		$response = $this->securitySettings->getForm();
		self::assertEquals('settings/security', $response->getTemplateName());
		self::assertEquals(['askUser' => 'askUserTestValue',], $response->getParams());
	}
}
