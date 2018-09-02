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
use OCA\Gravatar\Settings\SecuritySettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;

/**
 * This controller handles app settings requests.
 */
class SettingsController extends Controller {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * SettingsController constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
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
		$this->config->setAppValue(Application::APP_ID, SecuritySettings::SETTING_ASK_USER, $enabled);
		return new JSONResponse(['askUser' => $enabled,]);
	}
}
