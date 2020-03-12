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
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin API.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_element_parameters {

    public const CE_PARAM_NUMBER = 'number';
    public const CE_PARAM_BOOL = 'bool';
    public const CE_PARAM_STRING = 'string';

    public const CE_VALID_PARAMS = [
        self::CE_PARAM_NUMBER,
        self::CE_PARAM_BOOL,
        self::CE_PARAM_STRING
    ];

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
     * @return custom_element_parameters
     */
    public static function from_string(string $jsonstr) : custom_element_parameters {
        $jsonobj = json_decode($jsonstr, true);
        $res = new custom_element_parameters();

        if (empty($jsonobj)) {
            return $res;
        }

        return self::from_array($jsonobj);
    }

    /**
     * @param array $statusobject
     * @return custom_element_parameters
     */
    public static function from_array(array $statusobject = null) : custom_element_parameters {
        $res = new custom_element_parameters();

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
     * @param string $type
     * @throws \coding_exception
     */
    public function add_parameter(string $name, string $type): void {
        if (!in_array($type, self::CE_VALID_PARAMS)) {
            throw new \coding_exception('Invalid custom element parameter type detected: ' . $type);
        }
        $this->parameters[] = [
            'name' => $name,
            'type' => $type
        ];
    }

    /**
     * @param string $name
     * @return int
     * @throws \coding_exception
     */
    public function get_parameter_type(string $name): int {
        foreach ($this->parameters as $parameter) {
            if ($parameter['name'] === $name) {
                return $parameter['type'];
            }
        }
        throw new \coding_exception('Invalid custom element parameter name:' . $name);
    }
}
