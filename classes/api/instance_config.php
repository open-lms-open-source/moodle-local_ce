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
 * Plugin API.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Class instance_config
 * @package local_ce\api
 */
class instance_config {

    /**
     * @var array
     */
    private $parameters;

    /**
     * deferred_report_status constructor.
     */
    public function __construct() {
        $this->parameters = [];
    }

    /**
     * @param string $jsonstr
     * @return instance_config
     */
    public static function from_string(string $jsonstr) : instance_config {
        $jsonobj = json_decode($jsonstr, true);
        $res = new instance_config();

        if (empty($jsonobj)) {
            return $res;
        }

        return self::from_array($jsonobj);
    }

    /**
     * @param array $statusobject
     * @return instance_config
     */
    public static function from_array(array $statusobject = null) : instance_config {
        $res = new instance_config();

        if (empty($statusobject)) {
            return $res;
        }

        $res->parameters = $statusobject['parameters'];

        return $res;
    }

    /**
     * @return string
     */
    public function to_json_string() : string {
        return json_encode($this->to_array());
    }

    /**
     * @return array
     */
    public function to_array() : array {
        $arr = [];
        $arr['parameters'] = $this->parameters;
        return $arr;
    }

    /**
     * @return array
     */
    public function get_parameters(): array {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @param $value
     * @throws \coding_exception
     */
    public function add_parameter(string $name, $value): void {
        $this->parameters[] = [
            'name' => $name,
            'value' => $value,
        ];
    }
}
