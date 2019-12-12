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
 * Default controller
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

use local_ce\ce_loader;
use local_ce\model\instance;
use local_ce\model\set;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * Default controller
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class local_ce_controller_default extends mr_controller {

    /**
     * Plugin identifier.
     */
    const PLUGIN = 'local_ce';

    /**
     * Default screen.
     */
    public function view_action() {
        $setid = required_param('setid', PARAM_INT);

        $instances = array_values(instance::get_all_by_setid($setid)); // This resets indexes.
        $set = set::get_by_id($setid);
        return $this->output->render_from_template('local_ce/set_view',
            (object)[
                'instances' => $instances,
                'set' => $set,
            ]);
    }

    /**
     * Launch a component.
     */
    public function launch_action() {
        global $CFG, $PAGE;
        $instance = instance::get_by_id(required_param('instanceid', PARAM_INT));
        $PAGE->set_title($instance->ce->name);
        // Render component HTML.
        $sesskey = sesskey();
        $cename = $instance->ce->cename;
        $confightml = $this->print_config_as_html_attributes((array)json_decode($instance->config));
        $output = <<<HTML
<span id="local_ce_loader"></span>
<$cename sess-key="{$sesskey}"
         www-root="{$CFG->wwwroot}"
         $confightml
></$cename>
HTML;

        // Register component JS.
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, 'local_ce', 'module', $instance->ce->id);
        $wcloader = ce_loader::get_instance();
        foreach ($files as $file) {
            if ('application/x-javascript' !== $file->get_mimetype()) {
                continue;
            }
            $filename = $file->get_filename();
            $src = moodle_url::make_pluginfile_url($file->get_contextid(), 'local_ce', 'module',
                $file->get_itemid(), $file->get_filepath(), $filename)->out(false);
            $wcloader->register_component('local_ce/' . $instance->ce->cename, $src, 'text/javascript');
            break; // Only 1 file can be registered per component.
        }
        return $this->output->box($output, 'boxwidthwide');
    }

    /**
     * @param array $config
     */
    private function print_config_as_html_attributes($config) {
        $res = '';
        foreach ($config as $attribute => $value) {
            $res .= " $attribute=\"$value\"";
        }
        return $res;
    }
}
