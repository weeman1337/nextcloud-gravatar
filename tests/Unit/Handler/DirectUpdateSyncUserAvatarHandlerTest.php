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

namespace OCA\Gravatar\Tests\Unit\Handler;

use OCA\Gravatar\GlobalAvatar\GlobalAvatarService;
use OCA\Gravatar\Handler\DirectUpdateSyncUserAvatarHandler;
use OCP\Files\NotFoundException;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\IImage;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * This class provides test cases for the direct update avatar sync handler.
 */
class DirectUpdateSyncUserAvatarHandlerTest extends TestCase {
	/**
	 * Provides test data sets for the sync method test.
	 *
	 * @return array
	 */
	public function provideSyncTestData(): array {
		$userWithEmail = $this->createUserMock('user1', 'user1@example.com');
		$userWithoutEmail = $this->createUserMock('user1', '');

		$avatarImage = $this->getMockBuilder(IImage::class)
			->getMock();

		$avatar = $this->getMockBuilder(IAvatar::class)
			->getMock();

		$avatarManager = $this->getMockBuilder(IAvatarManager::class)
			->getMock();

		return [
			'valid user, avatar found' => [$userWithEmail, $avatarImage, $avatar, $avatarManager, $avatarImage,],
			'user without email' => [$userWithoutEmail, null, $avatar, $avatarManager, null,],
			'no avatar found' => [$userWithEmail, null, $avatar, $avatarManager, null,],
			'retrieving the avatar failed' => [$userWithEmail, null, new NotFoundException(), $avatarManager, null,],
			'setting the avatar failed' => [$userWithEmail, $avatarImage, $avatar, $avatarManager, new \Exception(),],
		];
	}

	/**
	 * Creates a user mock with uid and email set.
	 *
	 * @param string $uid The user id
	 * @param string|null $email The user's email
	 * @return IUser
	 */
	private function createUserMock(string $uid, $email): IUser {
		$user = $this->getMockBuilder(IUser::class)
			->getMock();
		$user->method('getUID')
			->willReturn($uid);
		$user->method('getEMailAddress')
			->willReturn($email);
		return $user;
	}

	/**
	 * Tests the sync method.
	 *
	 * @test
	 * @dataProvider provideSyncTestData
	 *
	 * @param IUser $user The user to run the test for
	 * @param IImage|null $globalAvatarServiceResult	The avatar service result
	 * @param IAvatar|\Exception $getAvatarResult The get avatar result.
	 *                                            Exception for expected exceptions.
	 * @param IAvatarManager $avatarManager The avatar manager injected into the handler.
	 * @param IImage|\Exception|null $expectedAvatarToSet The expected avatar to set.
	 *                                                       Null if no set is expected.
	 *                                                       Exception for expected expcetion.
	 * @return void
	 */
	public function testSync(
		$user,
		$globalAvatarServiceResult,
		$getAvatarResult,
		$avatarManager,
		$expectedAvatarToSet
	) {
		if ($getAvatarResult instanceof \Exception) {
			$avatarManager->method('getAvatar')
				->willThrowException($getAvatarResult);
		} else {
			$avatarManager->method('getAvatar')
				->with($user->getUID())
				->willReturn($getAvatarResult);

			if ($expectedAvatarToSet === null) {
				$getAvatarResult
					->expects($this->never())
					->method('set');
			} else if ($expectedAvatarToSet instanceof \Exception) {
				$getAvatarResult
					->method('set')
					->willThrowException($expectedAvatarToSet);
			} else {
				$getAvatarResult
					->expects($this->once())
					->method('set')
					->with($expectedAvatarToSet);
			}
		}

		$globalAvatarService = $this->getMockBuilder(GlobalAvatarService::class)
			->getMock();

		$globalAvatarService->method('query')
			->willReturn($globalAvatarServiceResult);
		/* @var GlobalAvatarService|MockObject $globalAvatarService */

		$avatarHandler = new DirectUpdateSyncUserAvatarHandler($globalAvatarService, $avatarManager);
		$avatarHandler->sync($user);
		self::assertTrue(true); // make phpunit happy
	}
}
