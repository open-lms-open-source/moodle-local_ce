/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Web components loader.
 *
 * @author    David Castro
 * @copyright Copyright (c) 2020 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['jquery'],
    function($) {

        return {
            /**
             * Initializes this module.
             * @param {String} componentsJson
             */
            init: function(componentsJson) {
                var components = JSON.parse(componentsJson);
                if (components !== {}) {
                    $.each(components, function(key, value) {
                        var scriptNode = document.createElement('script');
                        scriptNode.type = value.type;
                        scriptNode.src = value.src;
                        $('body').append(scriptNode);
                    });
                }
                $("#local_ce_loader").toggle();
            }
        };
    });
