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
 * Custom element model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\model;

defined ('MOODLE_INTERNAL') || die();

/**
 * Custom element model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_element extends abstract_model {

    const CETYPE_MANUAL = 1;

    const CETYPE_CHANNEL = 2;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $cename;

    /**
     * @var int
     */
    public $cetype;

    /**
     * @var string
     */
    public $channel;

    /**
     * @var string
     */
    public $defaulticon;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $parameters;

    /**
     * @var string
     */
    public $requirements;

    /**
     * @var int
     */
    public $version;

    /**
     * @var string
     */
    public $cdnurl;

    /**
     * @var string
     */
    public $cdnurles5;

    /**
     * @var string
     */
    public $editurl;

    /**
     * @var string
     */
    public $deleteurl;

    /**
     * custom_element constructor.
     * @param $id
     * @param $name
     * @param $cename
     * @param $cetype
     * @param $channel
     * @param $iconfileid
     * @param $defaulticon
     * @param $description
     * @param $parameters
     * @param $requirements
     * @param $timemodified
     * @param $version
     * @param $modulefileid
     * @param $cdnurl
     * @param $modulefilees5id
     * @param $cdnurles5
     */
    public function __construct($id, $name, $cename, $cetype, $channel, $defaulticon, $description,
                                $parameters, $requirements, $timemodified, $version, $cdnurl,
                                $cdnurles5) {
        $this->id = $id;
        $this->name = $name;
        $this->cename = $cename;
        $this->cetype = $cetype;
        $this->channel = $channel;
        $this->defaulticon = $defaulticon;
        $this->description = $description;
        $this->parameters = $parameters;
        $this->requirements = $requirements;
        $this->timemodified = $timemodified;
        $this->version = $version;
        $this->cdnurl = $cdnurl;
        $this->cdnurles5 = $cdnurles5;

        if (!is_null($this->id)) {
            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'editce',
                'ceid' => $this->id
            ]);
            $this->editurl = $murl->out(false);

            $murl = new \moodle_url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'deletece',
                'ceid' => $this->id
            ]);
            $this->deleteurl = $murl->out(false);
        }
    }

    /**
     * @return string
     */
    protected static function get_table(): string {
        return 'local_ce_custom_element';
    }

    /**
     * @return \stdClass
     */
    protected function to_record(): \stdClass {
        $record = new \stdClass();
        if (!is_null($this->id)) {
            $record->id = $this->id;
        }
        $record->name = $this->name;
        $record->cename = $this->cename;
        $record->cetype = $this->cetype;
        $record->channel = $this->channel;
        $record->defaulticon = $this->defaulticon;
        $record->description = $this->description;
        $record->parameters = $this->parameters;
        $record->requirements = $this->requirements;
        $record->version = $this->version;
        $record->cdnurl = $this->cdnurl;
        $record->cdnurles5 = $this->cdnurles5;
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
     * @return custom_element
     */
    protected static function from_record($record) {
        return new custom_element(
            $record->id,
            $record->name,
            $record->cename,
            $record->cetype,
            $record->channel,
            $record->defaulticon,
            $record->description,
            $record->parameters,
            $record->requirements,
            $record->timemodified,
            $record->version,
            $record->cdnurl,
            $record->cdnurles5
        );
    }
}
