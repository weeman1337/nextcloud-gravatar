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

namespace OCA\Gravatar\Tests\Hooks;

use OCA\Gravatar\Handler\SyncUserAvatarHandler;
use OCA\Gravatar\Hooks\UserSessionHook;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * This class provides test cases for the user session hooks.
 */
class UserSessionHookTest extends TestCase {
	/**
	 * @var IUser|MockObject
	 */
	private $user;

	/**
	 * @var IUserSession|MockObject
	 */
	private $userSession;

	/**
	 * @var SyncUserAvatarHandler|MockObject
	 */
	private $syncUserAvatarHandler;

	/**
	 * @var UserSessionHook
	 */
	private $userSessionHook;

	/**
	 * Setups objects for tests.
	 *
	 * @before
	 * @return void
	 */
	public function setupTestObjects() {
		$this->user = $this->getMockBuilder(IUser::class)
			->getMock();

		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->setMethods([
				'setUser',
				'getUser',
				'listen',
				'login',
				'logout',
				'isLoggedIn',
			])
			->getMock();
		$this->userSession
			->method('getUser')
			->willReturn($this->user);

		$this->syncUserAvatarHandler = $this->getMockBuilder(SyncUserAvatarHandler::class)
			->getMock();
		$this->userSessionHook = new UserSessionHook($this->userSession, $this->syncUserAvatarHandler);
	}

	/**
	 * Checks that register registers the user session on login hook.
	 *
	 * @test
	 * @return void
	 */
	public function register() {
		$this->userSession->expects($this->once())
			->method('listen')
			->with('\OC\User', 'postLogin', [$this->userSessionHook, 'onPostLogin']);
		$this->userSessionHook->register();
	}

	/**
	 * Checks that the avatar sync is triggered on login.
	 *
	 * @test
	 * @return void
	 */
	public function testOnPostLogin() {
		$this->syncUserAvatarHandler->expects($this->once())
			->method('sync')
			->with($this->user);
		$this->userSessionHook->onPostLogin();
	}
}
