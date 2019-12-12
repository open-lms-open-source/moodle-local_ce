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

use context_system;
use moodle_url;
use stored_file;

defined ('MOODLE_INTERNAL') || die();

/**
 * Custom element set model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set extends abstract_model {

    const SET_STATUS_DRAFT = 1;

    const SET_STATUS_PUBLISHED = 2;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $requiredcapability;

    /**
     * @var string
     */
    public $editurl;

    /**
     * @var string
     */
    public $deleteurl;

    /**
     * set constructor.
     * @param int $id
     * @param string $name
     * @param int $status
     * @param string $requiredcapability
     * @param int $timemodified
     */
    public function __construct($id, $name, $status, $requiredcapability = null, $timemodified = null) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->requiredcapability = $requiredcapability;
        $this->timemodified = $timemodified;

        if (!is_null($this->id)) {
            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'editset',
                'setid' => $this->id
            ]);
            $this->editurl = $murl->out(false);

            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'deleteset',
                'setid' => $this->id
            ]);
            $this->deleteurl = $murl->out(false);

            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'listinstances',
                'setid' => $this->id
            ]);
            $this->instancesurl = $murl->out(false);
        }

        $this->statusstr = '';
        switch($status) {
            case self::SET_STATUS_DRAFT:
                $this->statusstr = get_string('statusdraft', 'local_ce');
                break;
            case self::SET_STATUS_PUBLISHED:
                $this->statusstr = get_string('statuspublished', 'local_ce');
                break;
            default:
                throw new \coding_exception('$statusid is invalid.');
                break;
        }
    }

    /**
     * @return string
     */
    protected static function get_table() : string {
        return 'local_ce_set';
    }

    /**
     * @return \stdClass
     */
    protected function to_record() : \stdClass {
        $record = new \stdClass();
        if (!is_null($this->id)) {
            $record->id = $this->id;
        }
        $record->name = $this->name;
        $record->status = $this->status;
        $record->requiredcapability = $this->requiredcapability;
        $record->timemodified = $this->timemodified;
        return $record;
    }

    /**
     * @return array
     */
    public function validate() : array {
        return [];
    }

    /**
     * @param $record
     * @return set
     */
    protected static function from_record($record) {
        return new set(
            $record->id,
            $record->name,
            $record->status,
            $record->requiredcapability ?? null,
            $record->timemodified ?? null
        );
    }

    /**
     * @param array $caps
     * @return set[]
     * @throws \dml_exception
     */
    public static function get_all_published_with_caps($caps = []) : array {
        global $DB;

        $capquery = 'AND (tab.requiredcapability IS NULL ';
        $capparams = [];
        if (!empty($caps)) {
            [$iosql, $capparams] = $DB->get_in_or_equal($caps, SQL_PARAMS_NAMED);
            $capquery .= 'OR tab.requiredcapability ' . $iosql;
        }
        $capquery .= ')';

        $params = array_merge([
            'status' => self::SET_STATUS_PUBLISHED
        ], $capparams);

        $thetable = static::get_table();
        $query = <<<SQL
            SELECT *
              FROM {{$thetable}} tab
             WHERE tab.status = :status
                   $capquery
SQL;

        return array_map([get_called_class(), 'from_record'], $DB->get_records_sql($query, $params));
    }

    /**
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_icon_url() : string {
        $fs = get_file_storage();

        /** @var stored_file[] $files */
        $files = $fs->get_area_files(context_system::instance()->id, 'local_ce', 'icon_set', $this->id);
        // There should only be 1 file.
        foreach ($files as $file) {
            $mimetype = $file->get_mimetype();
            if(file_mimetype_in_typegroup($mimetype, 'web_image')) {
                return moodle_url::make_pluginfile_url($file->get_contextid(), 'local_ce', 'icon_set',
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename())->out(false);
            }
        }
    }

    /**
     * @return string
     * @throws \moodle_exception
     */
    public function get_view_url() : string {
        $murl = new moodle_url('/local/ce/view.php', ['setid' => $this->id, 'action' => 'view']);
        return $murl->out(false);
    }
}
