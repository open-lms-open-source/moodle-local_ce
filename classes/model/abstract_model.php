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
 * Custom element abstract model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\model;

defined('MOODLE_INTERNAL') || die();

/**
 * Custom element abstract model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_model {

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $timemodified;

    /**
     * @return array
     * @throws \dml_exception
     */
    public function save() {
        global $DB;
        $errors = $this->validate();
        if (!empty($errors)) {
            return $errors;
        }

        $this->set_timemodified_to_now();

        $record = $this->to_record();
        if (!is_null($this->id)) {
            $DB->update_record(static::get_table(), $record);
        } else {
            $this->id = $DB->insert_record(static::get_table(), $record);
        }
        return [];
    }

    /**
     * @return string
     */
    abstract protected static function get_table(): string;

    /**
     * @return \stdClass
     */
    abstract protected function to_record(): \stdClass;

    /**
     * @return array
     */
    abstract protected function validate(): array;

    /**
     * @param $record
     * @return abstract_model
     */
    abstract protected static function from_record($record);

    /**
     * Sets timemodified to now.
     */
    private function set_timemodified_to_now() {
        $this->timemodified = time();
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    public static function get_all() {
        global $DB;
        return array_map([get_called_class(), 'from_record'], $DB->get_records(static::get_table()));
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    public static function get_all_as_objects() {
        return array_map(function ($element) {
            return (object)(array)$element;
        }, static::get_all());;
    }

    /**
     * @param $id
     * @return abstract_model
     * @throws \dml_exception
     */
    public static function get_by_id($id) {
        global $DB;
        return static::from_record($DB->get_record(static::get_table(), ['id' => $id]));
    }

    /**
     * @throws \dml_exception
     */
    public function delete() {
        global $DB;
        if (!is_null($this->id)) {
            $DB->delete_records(static::get_table(), ['id' => $this->id]);
        }
    }
}
