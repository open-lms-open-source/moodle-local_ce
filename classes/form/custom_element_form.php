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
 * @author     David Castro <david.castro@openlms.net>
 * @copyright  Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\form;

defined('MOODLE_INTERNAL') || die('Forbidden.');

require_once("$CFG->libdir/formslib.php");

use local_ce\api\custom_element_parameters;
use local_ce\api\custom_element_requirements;
use local_ce\model\custom_element;
use moodleform;

/**
 * Custom element form.
 *
 * @package    local_ce
 * @author     David Castro <david.castro@openlms.net>
 * @copyright  Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_element_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('ce_form_name', 'local_ce'), ['size' => '30']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'cename', get_string('ce_form_cename', 'local_ce'), ['size' => '30']);
        $mform->setType('cename', PARAM_TEXT);
        $mform->addRule('cename', null, 'required', null, 'client');

        $mform->addElement('hidden', 'cetype', custom_element::CETYPE_MANUAL);
        $mform->setType('cetype', PARAM_INT);

        $mform->addElement('text', 'version', get_string('ce_form_version', 'local_ce'), ['size' => '30']);
        $mform->setType('version', PARAM_INT);
        $mform->addRule('version', null, 'required', null, 'client');

        $iconoptions = $this->get_iconfile_options(true);
        $mform->addElement('filemanager', 'iconfileid', get_string('ce_form_iconfile', 'local_ce'), null, $iconoptions);

        $defaulticonoptions = $this->create_icon_options();
        $mform->addGroup($defaulticonoptions, 'defaulticongroup', get_string('ce_form_defaulticon', 'local_ce'), [' '], false);
        $mform->setDefault('defaulticon', custom_element::CE_DEFAULT_ICON);

        $mform->addElement('textarea', 'description', get_string('ce_form_description', 'local_ce'),
            'wrap="virtual" rows="10" cols="50"');
        $mform->addElement('textarea', 'parameters', get_string('ce_form_parameters', 'local_ce'),
            'wrap="virtual" rows="10" cols="50"');

        $options = [
            'multiple' => true,
        ];
        $pluginopts = $this->get_plugin_list();
        $mform->addElement('autocomplete', 'requiredplugins', get_string('ce_form_requiredplugins', 'local_ce'), $pluginopts, $options);

        $moduleoptions = $this->get_modulefile_options(true);
        foreach (custom_element_requirements::MODULE_TYPES as $modtype) {
            $mform->addElement('filemanager', $modtype . 'fileid', get_string("ce_form_{$modtype}file", 'local_ce'), null, $moduleoptions);
            if (in_array($modtype, custom_element_requirements::MODULE_TYPES_REQUIRED)) {
                $mform->addRule($modtype . 'fileid', null, 'required', null, 'client');
            }

            $mform->addElement('select', "{$modtype}_script_type", get_string("ce_form_{$modtype}_script_type", 'local_ce'), custom_element_requirements::SCRIPT_TYPES);
            $mform->setType("{$modtype}_script_type", PARAM_INT);

            $mform->addElement('checkbox', "{$modtype}_script_nomodule",  get_string("ce_form_{$modtype}_script_nomodule", 'local_ce'));
            $mform->setType("{$modtype}_script_nomodule", PARAM_BOOL);
        }

        $this->add_action_buttons(false);
    }

    /**
     * The number cannot be negative.
     * @param array $data An array of form data
     * @param array $files An array of form files
     * @return array Error messages
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['parameters'])) {
            $jsonobj = json_decode($data['parameters']);
            if (is_null($jsonobj)) {
                $errors['parameters'] = get_string('ce_form_error_parameters_invalid', 'local_ce');
            }
        }

        $ce = $this->create_object_from_form_data(null, (object) $data);
        $errors = array_merge($errors, $ce->validate());
        return $errors;
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

    public function get_plugin_list() {
        static $pluginopts = [];
        if (!empty($pluginopts)) {
            return $pluginopts;
        }
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
                $pluginopts[$component.','.$version] = $component.','.$version;
            }
        }
        return $pluginopts;
    }

    /**
     * @param null|int $id
     * @param \stdClass $fromform
     * @return custom_element
     * @throws \coding_exception
     */
    public function create_object_from_form_data($id, $fromform): custom_element {
        $requirements = new custom_element_requirements();
        $pluginopts = $this->get_plugin_list();
        foreach ($fromform->requiredplugins as $reqplugin) {
            [$plugin, $version] = explode(',', $pluginopts[$reqplugin]);
            $requirements->add_plugin($plugin, $version);
        }

        foreach (custom_element_requirements::MODULE_TYPES as $modtype) {
            $requirements->set_libraryconfig(
                $modtype,
                custom_element_requirements::SCRIPT_TYPES[$fromform->{"{$modtype}_script_type"}],
                !empty($fromform->{"{$modtype}_script_nomodule"}));
        }

        $parameters = custom_element_parameters::from_string($fromform->parameters);

        $ce = new custom_element(
            $id,
            $fromform->name,
            $fromform->cename,
            $fromform->cetype,
            null,
            $fromform->defaulticon,
            $fromform->description,
            $parameters,
            $requirements,
            null,
            $fromform->version,
            null,
            null
        );

        return $ce;
    }

    /**
     * @return array
     */
    private function create_icon_options() {
        $mform = $this->_form;
        $options = [
            custom_element::CE_ICON_CIRCLE,
            custom_element::CE_ICON_TRIANGLE,
            custom_element::CE_ICON_DIAMOND,
            custom_element::CE_ICON_SQUARE,
            custom_element::CE_ICON_PENTAGON,
        ];

        $imgoptions = [];

        foreach ($options as $option) {
            $html = "<div class=\"local-ce-icon-ce local-ce-icon-ce-{$option}\"></div>";
            $imgoptions[] =& $mform->createElement('radio', 'defaulticon', '', $html, $option);
        }

        return $imgoptions;
    }
}
