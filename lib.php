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
 * Convenient wrappers and helper for using the Custom elements Local plugin.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook for adding things before footer.
 */
function local_ce_before_footer() {
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

/**
 * Serves 3rd party js files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_ce_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $pluginpath = __DIR__ . '/';

    $supportedfileareas = ['icon', 'module', 'modulees5', 'icon_set'];

    if ($filearea === 'vendorjs') {
        // Typically CDN fall backs would go in vendorjs.
        $path = $pluginpath . 'vendorjs/' . implode('/', $args);
        send_file($path, basename($path));
        return true;
    } else if (in_array($filearea, $supportedfileareas)) {
        $itemid = array_shift($args); // The first item in the $args array.
        $filename = array_pop($args); // The last item in the $args array.
        if (!$args) {
            $filepath = '/'; // $args is empty => the path is '/'
        } else {
            $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
        }
        // Retrieve the file from the Files API.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_ce', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false; // The file does not exist.
        }
        // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
        send_stored_file($file, 86400, 0, $forcedownload, $options);
    } else {
       return false;
    }
}

/**
 * Adds the dock to the footer html.
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_ce_add_dock_to_footer() {
    global $CFG, $OUTPUT, $PAGE;

    static $added = false;
    if (!$added) {
        $added = true;
    } else {
        return;
    }

    if (empty($CFG->local_ce_enable_usage)) {
        return;
    }

    $currentcaps = [];
    $capstocheck = [
        'local/ce:learnerset_view',
        'local/ce:instructorset_view'
    ];
    // Check for caps.
    foreach ($capstocheck as $cap) {
        if (has_capability($cap, $PAGE->context)) {
            $currentcaps[] = $cap;
        }
    }

    $sets = \local_ce\model\set::get_all_published_with_caps($currentcaps);
    if (empty($sets)) {
        return;
    }

    $setstorender = [];
    foreach ($sets as $set) {
        $settorender = [];
        $settorender['name'] = $set->name;
        $settorender['seturl'] = $set->get_view_url();
        $settorender['iconurl'] = !is_null($set->iconurl) ? $set->iconurl : $OUTPUT->image_url('set-' . $set->defaulticon, 'local_ce')->out(false);
        $setstorender[] = (object)$settorender;
    }

    $template = $OUTPUT->render_from_template('local_ce/set_dock', (object)[
        'sets' => $setstorender
    ]);

    if (!isset($CFG->additionalhtmlfooter)) {
        $CFG->additionalhtmlfooter = '';
    }
    $CFG->additionalhtmlfooter .= $template;
}

/**
 * Used since Moodle 29.
 */
function local_ce_extend_navigation() {
    local_ce_add_dock_to_footer();
}

/**
 * Used since Moodle 29.
 */
function local_ce_extend_settings_navigation() {
    local_ce_add_dock_to_footer();
}

/**
 * Used in Moodle 30+ when a user is logged on.
 */
function local_ce_extend_navigation_user_settings() {
    local_ce_add_dock_to_footer();
}

/**
 * Used in Moodle 30+ on the frontpage.
 */
function local_ce_extend_navigation_frontpage() {
    local_ce_add_dock_to_footer();
}

/**
 * Used in Moodle 31+ when a user is logged on.
 */
function local_ce_extend_navigation_user() {
    local_ce_add_dock_to_footer();
}

