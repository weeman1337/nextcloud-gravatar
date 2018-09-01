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

namespace OCA\Gravatar\GlobalAvatar;

use OC\AppFramework\Http;
use OCA\Gravatar\Image\GImage;
use OCP\Http\Client\IClient;
use OCP\IImage;
use OCP\IUser;

/**
 * Global avatar service implementation using gravatar to query the avatar.
 */
class GravatarService implements GlobalAvatarService {

	const GRAVATAR_URL = 'https://www.gravatar.com/avatar/%s?d=404&s=200';

	/**
	 * @var IClient
	 */
	private $httpClient;

	/**
	 * GravatarService constructor.
	 *
	 * @param IClient $httpClient
	 */
	public function __construct(IClient $httpClient) {
		$this->httpClient = $httpClient;
	}

	/**
	 * Retrieves the user's global avatar from gravatar.
	 *
	 * @param IUser $user
	 * @return null|IImage
	 */
	public function query(IUser $user) {
		$email = $user->getEMailAddress();
		if (empty($email) === false) {
			$response = $this->fetchFromGravatar($email);
		} else {
			$response = null;
		}
		return $response;
	}

	/**
	 * Fetches the global avatar from gravatar.
	 *
	 * @param string $email	The email address to retrieve the global avatar for.
	 * @return null|IImage The avatar as image or null if none found.
	 */
	private function fetchFromGravatar(string $email) {
		$emailHash = $this->getHashedEmail($email);
		$requestUrl = $this->buildGravatarRequestUrl($emailHash);

		try {
			$response = $this->httpClient->get($requestUrl);
		} catch (\Exception $e) {
			return null;
		}

		$avatar = null;

		if ($response->getStatusCode() === Http::STATUS_OK) {
			$avatarData = $response->getBody();
			$avatarImage = new GImage();
			if ($avatarImage->loadFromData($avatarData) !== false) {
				 $avatar = $avatarImage;
			}
		}

		return $avatar;
	}

	/**
	 * Hashes the email for gravatar.
	 *
	 * @param string $email
	 * @return string
	 */
	private function getHashedEmail(string $email): string {
		$trimmed = trim($email);
		$lowerCased = strtolower($trimmed);
		$hashed = md5($lowerCased);
		return $hashed;
	}

	/**
	 * Builds the gravatar avatar request url.
	 *
	 * @param string $emailHash The email hash
	 * @return string
	 */
	private function buildGravatarRequestUrl(string $emailHash): string {
		return sprintf(self::GRAVATAR_URL, $emailHash);
	}
}
