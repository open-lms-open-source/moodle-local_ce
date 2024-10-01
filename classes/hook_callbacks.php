<?php
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
 * @package   local_ce
 * @author    Jonathan Garcia Gomez <jonathan.garcia@openlms.net>
 * @copyright Copyright (c) 2024 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

namespace local_ce;

class hook_callbacks {

    /**
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $CFG, $PAGE;

        if (!isloggedin()) {
            return;
        }

        $wcloader = \local_ce\ce_loader::get_instance();

        if (!empty(get_config('local_ce', 'enablemv'))) {
            // Register Model viewer.
            $wcloader->register_component('local_ce/model-viewer',
                $CFG->wwwroot . '/pluginfile.php/' . $PAGE->context->id . '/local_ce/' . 'vendorjs/model-viewer.js');
        }

        // Load components.
        $wcloader->load_components();
    }
}