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

namespace OCA\Gravatar\Tests\Unit\GlobalAvatar;

use OC\AppFramework\Http;
use OCA\Gravatar\GlobalAvatar\GlobalAvatarService;
use OCA\Gravatar\GlobalAvatar\GravatarService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\IImage;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * This class provides test cases for the gravatar service.
 */
class GravatarServiceTest extends TestCase {
	/**
	 * @var IClient|MockObject
	 */
	private $httpClient;

	/**
	 * @var IResponse|MockObject
	 */
	private $httpResponse;

	/**
	 * @var IUser|MockObject
	 */
	private $user;

	/**
	 * @var GlobalAvatarService
	 */
	private $gravatarService;

	/**
	 * Setups objects for tests.
	 *
	 * @before
	 * @return void
	 */
	public function setupTestObjects() {
		$this->httpClient = $this->getMockBuilder(IClient::class)
			->getMock();
		$this->httpResponse = $this->getMockBuilder(IResponse::class)
			->getMock();
		$this->user = $this->getMockBuilder(IUser::class)
			->getMock();
		$this->gravatarService = new GravatarService($this->httpClient);
	}

	/**
	 * Tests the regular gravatar query.
	 *
	 * @return void
	 */
	public function testQuery() {
		$avatarSource = file_get_contents(__DIR__ . '/../../data/mweimann.jpeg');

		$this->user->method('getEMailAddress')
			->willReturn(' AsD@example.com');

		$this->httpResponse->method('getStatusCode')
			->willReturn(Http::STATUS_OK);
		$this->httpResponse->method('getBody')
			->willReturn($avatarSource);

		$expectedUrl = 'https://www.gravatar.com/avatar/3ef696e12ea92e79e6395a18166a3e51?d=404&s=200';
		$this->httpClient->expects($this->once())
			->method('get')
			->with($expectedUrl)
			->willReturn($this->httpResponse);

		$result = $this->gravatarService->query($this->user);
		self::assertInstanceOf(IImage::class, $result);
	}

	/**
	 * Tests the case the http client raises an exception.
	 *
	 * @test
	 * @return void
	 */
	public function testHttpClientException() {
		$this->user->method('getEMailAddress')
			->willReturn('asd@example.com');
		$this->httpClient->method('get')
			->willThrowException(new \Exception());
		self::assertNull($this->gravatarService->query($this->user));
	}

	/**
	 * Tests the case the user email is empty.
	 *
	 * @test
	 * @return void
	 */
	public function testEmptyEmail() {
		$this->user->method('getEMailAddress')
			->willReturn('');
		self::assertNull($this->gravatarService->query($this->user));
	}
}
