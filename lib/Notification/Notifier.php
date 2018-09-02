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

namespace OCA\Gravatar\Notification;

use OCA\Gravatar\AppInfo\Application;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

/**
 * Gravatar notifier implementation.
 */
class Notifier implements INotifier {
	/**
	 * @var IFactory
	 */
	private $lFactory;

	/**
	 * Notifier constructor.
	 *
	 * @param IFactory $lFactory
	 */
	public function __construct(IFactory $lFactory) {
		$this->lFactory = $lFactory;
	}

	/**
	 * Configures notifications for display.
	 *
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new \InvalidArgumentException();
		}

		$l = $this->lFactory->get(Application::APP_ID, $languageCode);

		switch ($notification->getSubject()) {
			case 'useGravatar':
				$this->prepareUseGravatarNotification($l, $notification);
				break;
			default:
				throw new \InvalidArgumentException();
		}

		return $notification;
	}

	/**
	 * Prepares the "useGravatar" notification.
	 *
	 * @param IL10N $l
	 * @param INotification $notification
	 * @return void
	 */
	private function prepareUseGravatarNotification(IL10N $l, INotification $notification) {
		$notification->setParsedSubject((string) $l->t('Gravatar integration'));
		$notification->setParsedMessage(
			(string) $l->t('Do you want to use Gravatar for displaying your avatar?')
		);

		$parsedActions = $notification->getParsedActions();

		if (empty($parsedActions) === true) {
			foreach ($notification->getActions() as $action) {
				switch ($action->getLabel()) {
					case 'confirm':
						$action->setParsedLabel($l->t('yes'));
						$action->setPrimary(true);
						break;
					case 'decline':
						$action->setParsedLabel($l->t('no'));
						break;
				}

				$notification->addParsedAction($action);
			}
		}
	}
}
