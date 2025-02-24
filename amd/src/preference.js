// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Permet de récupérer les préférences utilisateurs
 *
 * @module     local_apsolu/preference
 * @copyright  2025 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Reference : https://tracker.moodle.org/browse/MDL-76974.
import {call as fetchMany} from 'core/ajax';

/**
 * Get single user preference
 *
 * @param {String} name Name of the preference
 * @param {Number} userid User ID (defaults to current user)
 * @return {Promise}
 */
export const getUserPreference = (name, userid = 0) => {
    return getUserPreferences(name, userid)
        .then(response => response.preferences[0].value);
};

/**
 * Get multiple user preferences
 *
 * @param {String|null} name Name of the preference (omit if you want to retrieve all)
 * @param {Number} userid User ID (defaults to current user)
 * @return {Promise}
 */
export const getUserPreferences = (name = null, userid = 0) => {
    return fetchMany([{
        methodname: 'core_user_get_user_preferences',
        args: {name, userid}
    }])[0];
};

/**
 * Set single user preference
 *
 * @param {String} name Name of the preference
 * @param {String|null} value Value of the preference (omit if you want to remove the current value)
 * @param {Number} userid User ID (defaults to current user)
 * @return {Promise}
 */
export const setUserPreference = (name, value = null, userid = 0) => {
    return setUserPreferences([{name, value, userid}]);
};

/**
 * Set multiple user preferences
 *
 * @param {Object[]} preferences Array of preferences containing name/value/userid attributes
 * @return {Promise}
 */
export const setUserPreferences = (preferences) => {
    return fetchMany([{
        methodname: 'core_user_set_user_preferences',
        args: {preferences}
    }])[0];
};
