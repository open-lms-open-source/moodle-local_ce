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
 * Test Instance DAO.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_ce\model\custom_element;
use local_ce\model\instance;
use local_ce\model\set;

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_instance_custom_element_testcase
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_instance_custom_element_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_dao_persist() {
        $set = new set(
            null,
            'ThisIsASet',
            set::SET_STATUS_PUBLISHED,
            set::SET_DEFAULT_ICON,
            null,
            null);
        $set->save();
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
        $ce->save();
        $instance = new instance(
            null,
            'Instance of Custom element',
            $ce->id,
            $set->id,
            '',
            null);
        $this->assertNull($instance->id);
        $instance->save();
        $this->assertNotNull($instance->id);
    }
}
