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

$(document).ready(function() {

	var useGravatarCheckbox = $('#use-gravatar');
	var useGravatarLoader = $('#use-gravatar-loading');

	useGravatarCheckbox.on('change', function() {
		var askUser = useGravatarCheckbox.is(':checked');
		var url = OC.generateUrl('/apps/gravatar/settings/useGravatar/');

		if (askUser === true) {
			url += 'enable';
		} else {
			url += 'disable';
		}

		useGravatarLoader.show();

		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: url,
			success: function(resp) {
				useGravatarLoader.hide();
			},
			error: function() {
				// revert on error
				useGravatarCheckbox.prop('checked', !askUser);
				useGravatarLoader.hide();
			}
		});
	});
});
