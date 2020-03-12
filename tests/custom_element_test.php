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
 * Test Custom element DAO.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_ce\model\custom_element;

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_ce_custom_element_testcase
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ce_custom_element_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_dao_persist() {
        $ce = new custom_element(
            null,
            'Custom element',
            'custom-element',
            custom_element::CETYPE_MANUAL,
            null,
            custom_element::CE_DEFAULT_ICON,
            '',
            new \local_ce\api\custom_element_parameters(),
            new \local_ce\api\custom_element_requirements(),
            time(),
            1,
            null,
            null);
        $this->assertNull($ce->id);
        $ce->save();
        $this->assertNotNull($ce->id);
    }

    public function test_dao_validation() {
        $ce = new custom_element(
            null,
            '',
            'BadName',
            custom_element::CETYPE_MANUAL,
            null, custom_element::CE_DEFAULT_ICON,
            '',
            new \local_ce\api\custom_element_parameters(),
            new \local_ce\api\custom_element_requirements(),
            time(),
            null,
            null,
            null);
        $errors = $ce->validate();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('cename', $errors);
        $this->assertArrayHasKey('version', $errors);
    }

}
