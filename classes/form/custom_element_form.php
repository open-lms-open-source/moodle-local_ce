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
 * Custom element form.
 *
 * @package    local_ce
 * @author     David Castro <david.castro@blackboard.com>
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\form;

defined('MOODLE_INTERNAL') || die('Forbidden.');

require_once("$CFG->libdir/formslib.php");

use local_ce\api\custom_element_requirements;
use local_ce\model\custom_element;
use moodleform;

/**
 * Custom element form.
 *
 * @package    local_ce
 * @author     David Castro <david.castro@blackboard.com>
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_element_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('ce_form_name', 'local_ce'), ['size' => '30']);
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'cename', get_string('ce_form_cename', 'local_ce'), ['size' => '30']);
        $mform->setType('cename', PARAM_TEXT);

        $mform->addElement('hidden', 'cetype', custom_element::CETYPE_MANUAL);
        $mform->setType('cetype', PARAM_INT);

        $iconoptions = $this->get_iconfile_options(true);
        $mform->addElement('filemanager', 'iconfileid', get_string('ce_form_iconfile', 'local_ce'), null, $iconoptions);

        $mform->addElement('text', 'defaulticon', get_string('ce_form_defaulticon', 'local_ce'), ['size' => '30']);
        $mform->setType('defaulticon', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('ce_form_description', 'local_ce'),
            'wrap="virtual" rows="10" cols="50"');
        $mform->addElement('textarea', 'parameters', get_string('ce_form_parameters', 'local_ce'),
            'wrap="virtual" rows="10" cols="50"');

        // Get list of enabled plugins.
        $pluginmanager = \core_plugin_manager::instance();
        $types = $pluginmanager->get_plugin_types();
        foreach (array_keys($types) as $type) {
            $plugins = $pluginmanager->get_installed_plugins($type);
            if ($plugins === false) {
                continue;
            }
            foreach ($plugins as $pluginname => $version) {
                $component = $type.'_'.$pluginname;
                $directory = \core_component::get_component_directory($component);
                if (!$directory || !is_dir($directory)) {
                    continue;  // Core plugin no longer exists.
                }
                $pluginopts[] = $component.','.$version;
            }
        }
        $options = [
            'multiple' => true,
        ];
        $mform->addElement('autocomplete', 'requiredplugins', get_string('ce_form_requiredplugins', 'local_ce'), $pluginopts, $options);

        $mform->addElement('text', 'version', get_string('ce_form_version', 'local_ce'), ['size' => '30']);
        $mform->setType('version', PARAM_INT);

        $moduleoptions = $this->get_modulefile_options(true);
        $mform->addElement('filemanager', 'modulefileid', get_string('ce_form_modulefile', 'local_ce'), null, $moduleoptions);

        $scriptopts = [
            custom_element_requirements::SCRIPT_TYPE_DEFAULT,
            custom_element_requirements::SCRIPT_TYPE_MODULE,
        ];
        $mform->addElement('select', 'module_script_type', get_string('ce_form_module_script_type', 'local_ce'), $scriptopts);
        $mform->setType('module_script_type', PARAM_INT);

        $mform->addElement('checkbox', 'module_script_nomodule',  get_string('ce_form_module_script_nomodule', 'local_ce'));
        $mform->setType('module_script_nomodule', PARAM_BOOL);

        $modulees5options = $this->get_modulefile_options(true);
        $mform->addElement('filemanager', 'modulefilees5id', get_string('ce_form_modulefilees5', 'local_ce'), null, $modulees5options);

        $mform->addElement('select', 'modulees5_script_type', get_string('ce_form_modulees5_script_type', 'local_ce'), $scriptopts);
        $mform->setType('module_script_type', PARAM_INT);

        $mform->addElement('checkbox', 'modulees5_script_nomodule',  get_string('ce_form_modulees5_script_nomodule', 'local_ce'));
        $mform->setType('module_script_nomodule', PARAM_BOOL);

        $this->add_action_buttons(false);
    }

    public function validation($data, $files) {
        return parent::validation($data, $files); // TODO: Change the autogenerated stub
    }

    /**
     * @param bool $forform
     * @return array
     */
    public function get_iconfile_options($forform = false) {
        $opts = [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
        ];

        if ($forform) {
            $opts['accepted_types'] = [
                'web_image'
            ];
            $opts['return_types'] = FILE_INTERNAL | FILE_EXTERNAL;
        }

        return $opts;
    }

    /**
     * @param bool $forform
     * @return array
     */
    public function get_modulefile_options($forform = false) {
        $opts = [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
        ];

        if ($forform) {
            $opts['accepted_types'] = [
                'js'
            ];
            $opts['return_types'] = FILE_INTERNAL | FILE_EXTERNAL;
        }

        return $opts;
    }
}
