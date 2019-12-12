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
 * @package   local_ce
 * @author    David Castro <osdev@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/log'],
    function($, Templates, Log) {

        return {
            /**
             * Initializes this module.
             * @param sets
             */
            init: function(sets) {
                var setArray = [];
                setArray['sets'] = sets;
                Templates.render('local_ce/set_dock', setArray)
                .then(function(html) {
                    $('body').append(html);
                    $('#local-ce-dock-container')
                        .animate({
                            opacity: 1
                        }, 700);
                })
                .fail(function(ex) {
                    Log.debug(ex);
                });
            }
        };
    });
