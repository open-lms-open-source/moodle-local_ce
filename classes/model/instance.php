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
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\model;

defined ('MOODLE_INTERNAL') || die();

/**
 * Custom element set model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance extends abstract_model {
    /**
     * @var string
     */
    public $customname;

    /**
     * @var int
     */
    public $customelementid;

    /**
     * @var int
     */
    public $setid;

    /**
     * @var string
     */
    public $config;

    /**
     * @var custom_element
     */
    public $ce;

    /**
     * @var string
     */
    public $editurl;

    /**
     * @var string
     */
    public $deleteurl;

    /**
     * @var string
     */
    public $launchurl;

    /**
     * @var string|null
     */
    public $iconurl;

    /**
     * instance constructor.
     * @param $id
     * @param $customname
     * @param $customelementid
     * @param $setid
     * @param $config
     * @param $timemodified
     */
    public function __construct($id, $customname, $customelementid, $setid, $config, $timemodified) {
        $this->id = $id;
        $this->customname = $customname;
        $this->customelementid = $customelementid;
        $this->setid = $setid;
        $this->config = $config;
        $this->timemodified = $timemodified;

        $this->ce = custom_element::get_by_id($this->customelementid);

        if (!is_null($this->id)) {
            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'editinstance',
                'setid' => $this->setid,
                'instanceid' => $this->id
            ]);
            $this->editurl = $murl->out(false);

            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'deleteinstance',
                'setid' => $this->setid,
                'instanceid' => $this->id
            ]);
            $this->deleteurl = $murl->out(false);

            $murl = new \moodle_url('/local/ce/view.php', [
                'action' => 'launch',
                'instanceid' => $this->id
            ]);
            $this->launchurl = $murl->out(false);

            $this->iconurl = $this->ce->get_icon_file_url();
        }
    }

    /**
     * @return string
     */
    protected static function get_table(): string {
        return 'local_ce_instance';
    }

    /**
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        $record = new \stdClass();
        if (!is_null($this->id)) {
            $record->id = $this->id;
        }
        $record->customname = $this->customname;
        $record->customelementid = $this->customelementid;
        $record->setid = $this->setid;
        $record->config = $this->config;
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
     * @return instance
     */
    protected static function from_record($record) {
        return new instance(
            $record->id,
            $record->customname,
            $record->customelementid,
            $record->setid,
            $record->config,
            $record->timemodified
        );
    }

    /**
     * @param $setid
     * @return array
     */
    public static function get_all_by_setid($setid) {
        global $DB;
        return array_map([get_called_class(), 'from_record'], $DB->get_records(static::get_table(), ['setid' => $setid]));
    }
}
