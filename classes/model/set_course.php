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
 * Custom element set model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\model;

defined ('MOODLE_INTERNAL') || die();

/**
 * Custom element set model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_course extends abstract_model {

    const SET_STATUS_DRAFT = 1;

    const SET_STATUS_PUBLISHED = 2;

    /**
     * @var int
     */
    public $setid;

    /**
     * @var int
     */
    public $courseid;

    /**
     * set constructor.
     * @param $id
     * @param $setid
     * @param $courseid
     */
    public function __construct($id, $setid, $courseid, $timemodified) {
        $this->id = $id;
        $this->setid = $setid;
        $this->courseid = $courseid;
        $this->timemodified = $timemodified;
    }

    /**
     * @return string
     */
    protected static function get_table(): string {
        return 'local_ce_set_course';
    }

    /**
     * @return \stdClass
     */
    protected function to_record(): \stdClass {
        $record = new \stdClass();
        if (!is_null($this->id)) {
            $record->id = $this->id;
        }
        $record->setid = $this->setid;
        $record->courseid = $this->courseid;
        $record->timemodified = $this->timemodified;
        return $record;
    }

    /**
     * @return array
     */
    public function validate(): array {
        return [];
    }

    /**
     * @param $record
     * @return set_course
     */
    protected static function from_record($record) {
        return new set_course(
            $record->id,
            $record->setid,
            $record->courseid,
            $record->timemodified
        );
    }

    /**
     * @param $setid
     * @return set_course[]
     * @throws \dml_exception
     */
    public static function get_all_by_setid($setid) {
        global $DB;
        return array_map([get_called_class(), 'from_record'], $DB->get_records(static::get_table(), [
            'setid' => $setid,
        ]));
    }

    /**
     * @param $courseid
     * @return set_course[]
     * @throws \dml_exception
     */
    public static function get_all_by_courseid($courseid) {
        global $DB;
        return array_map([get_called_class(), 'from_record'], $DB->get_records(static::get_table(), [
            'courseid' => $courseid,
        ]));
    }

    /**
     * @param $setid
     * @param $courseid
     * @return set_course[]
     * @throws \dml_exception
     */
    public static function get_all_by_setid_and_courseid($setid, $courseid) {
        global $DB;
        return array_map([get_called_class(), 'from_record'], $DB->get_records(static::get_table(), [
            'setid' => $setid,
            'courseid' => $courseid,
        ]));
    }

    /**
     * @param int $setid
     * @param int[] $courseids
     * @return set_course[]
     * @throws \dml_exception
     */
    public static function get_all_by_setid_and_courseids($setid, $courseids) {
        global $DB;
        $table = static::get_table();
        $params = ['setid' => $setid];
        [$coursidinoreq, $inoreqparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $query = <<<SQL
            SELECT *
              FROM {{$table}}
             WHERE setid = :setid
               AND courseid $coursidinoreq
SQL;
        $params = array_merge($params, $inoreqparams);
        return array_map([get_called_class(), 'from_record'], $DB->get_records_sql($query, $params));
    }

    /**
     * @param int $setid
     * @param int[] $courseids
     * @return set_course[]
     * @throws \dml_exception
     */
    public static function get_all_by_setid_and_not_in_courseids($setid, $courseids) {
        global $DB;
        $table = static::get_table();
        $params = ['setid' => $setid];
        [$coursidinoreq, $inoreqparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'param', false);
        $query = <<<SQL
            SELECT *
              FROM {{$table}}
             WHERE setid = :setid
               AND courseid $coursidinoreq
SQL;
        $params = array_merge($params, $inoreqparams);
        return array_map([get_called_class(), 'from_record'], $DB->get_records_sql($query, $params));
    }

    /**
     * @param $setid
     * @param $courseids
     */
    public static function delete_not_in_courseids($setid, $courseids) {
        global $DB;

        $table = static::get_table();
        $params = ['setid' => $setid];

        $coursesql = '';
        if (!empty($courseids)) {
            [$coursidinoreq, $inoreqparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'param', false);
            $coursesql = "AND courseid $coursidinoreq";
            $params = array_merge($params, $inoreqparams);
        }

        $query = <<<SQL
            DELETE
              FROM {{$table}}
             WHERE setid = :setid
                   $coursesql
SQL;

        $DB->execute($query, $params);
    }

    /**
     * @param set $set
     * @param int[] $courseids
     */
    public static function sync_set_with_courses($set, $courseids) {
        // Delete those which are not in current set.
        self::delete_not_in_courseids($set->id, $courseids);
        if (empty($courseids)) {
            return; // Should have already deleted all entries and nothing will be inserted.
        }

        $existing = self::get_all_by_setid_and_courseids($set->id, $courseids);
        $existingcourseids = [];
        foreach ($existing as $setcourse) {
            $existingcourseids = $setcourse->courseid;
        }
        $remaining = array_diff($courseids, $existingcourseids);
        foreach ($remaining as $courseid) {
            $setcourse = new set_course(
                null,
                $set->id,
                $courseid,
                null
            );
            $setcourse->save();
        }
    }
}
