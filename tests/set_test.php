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
 * Test Set DAO.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_ce\model\set;

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_ce_set_testcase
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ce_set_testcase extends advanced_testcase {

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
        $this->assertNull($set->id);
        $set->save();
        $this->assertNotNull($set->id);
    }

    public function test_get_all_published_with_caps() {
        $capabilityopts = [
            null,
            'local/ce:learnerset_view',
            'local/ce:instructorset_view',
        ];
        foreach ($capabilityopts as $capabilityopt) {
            $set = new set(
                null,
                'ThisIsASet',
                set::SET_STATUS_PUBLISHED,
                set::SET_DEFAULT_ICON,
                $capabilityopt,
                null);
            $this->assertNull($set->id);
            $set->save();
        }

        foreach ($capabilityopts as $capabilityopt) {
            if (is_null($capabilityopt)) {
                // Only 1 null cap set.
                $this->assertCount(1, set::get_all_published_with_caps());
            } else {
                // Someone with a cap will get their set and the set with no caps.
                $this->assertCount(2, set::get_all_published_with_caps([$capabilityopt]));
            }
        }

        // A manager has all caps.
        $this->assertCount(3, set::get_all_published_with_caps($capabilityopts));
    }

}
