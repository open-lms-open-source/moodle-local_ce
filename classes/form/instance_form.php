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
 * Instance form.
 *
 * @package    local_ce
 * @author     David Castro <david.castro@openlms.net>
 * @copyright  Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\form;

defined('MOODLE_INTERNAL') || die('Forbidden.');

require_once("$CFG->libdir/formslib.php");

use local_ce\model\custom_element;
use moodleform;

/**
 * Instance form.
 *
 * @package    local_ce
 * @author     David Castro <david.castro@openlms.net>
 * @copyright  Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;

        // Adding the standard "name" field.
        $mform->addElement('text', 'customname', get_string('instance_form_customname', 'local_ce'), ['size' => '30']);
        $mform->setType('customname', PARAM_TEXT);
        $mform->addRule('customname', null, 'required');

        $ces = custom_element::get_all();
        $ceopts = [];
        foreach ($ces as $ce) {
            $ceopts[$ce->id] = $ce->name;
        }
        $mform->addElement('select', 'customelementid', get_string('instance_form_customelementid', 'local_ce'), $ceopts);
        $mform->setType('customelementid', PARAM_INT);

        $mform->addElement('textarea', 'config', get_string('instance_form_config', 'local_ce'),
            'wrap="virtual" rows="10" cols="50"');

        $this->add_action_buttons(false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['config'])) {
            $jsonobj = json_decode($data['config']);
            if (is_null($jsonobj)) {
                $errors['config'] = get_string('instance_form_error_config_invalid', 'local_ce');
            }
        }

        return $errors;
    }

}
