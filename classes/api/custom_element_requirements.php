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
 * Class custom_element_requirements
 * @package local_ce\api
 */
class custom_element_requirements {

    const SCRIPT_TYPE_MODULE = 'module';
    const SCRIPT_TYPE_DEFAULT = 'text/javascript';
    const SCRIPT_TYPES = [self::SCRIPT_TYPE_DEFAULT, self::SCRIPT_TYPE_MODULE];

    const MODULE_TYPE_MODULE = 'module';
    const MODULE_TYPE_MODULE_ES5 = 'modulees5';
    const MODULE_TYPES = [self::MODULE_TYPE_MODULE, self::MODULE_TYPE_MODULE_ES5];

    const MODULE_TYPES_REQUIRED = [self::MODULE_TYPE_MODULE];

    /**
     * @var string[]
     */
    private $plugins;

    /**
     * @var string[]
     */
    private $libraryconfig;

    /**
     * deferred_report_status constructor.
     */
    public function __construct() {
        $this->plugins = [];

        $this->libraryconfig = [];
        foreach (self::MODULE_TYPES as $moduletype) {
            $this->libraryconfig[] = [
                'moduletype' => $moduletype,
                'type' => self::SCRIPT_TYPE_DEFAULT,
                'nomodule' => false,
            ];
        }
    }

    /**
     * @param string $config
     * @return custom_element_requirements
     */
    public static function from_string(string $config) : custom_element_requirements {
        $jsonobj = json_decode($config, true);
        $res = new custom_element_requirements();

        if (empty($jsonobj)) {
            return $res;
        }

        return self::from_array($jsonobj);
    }

    /**
     * @param array $statusobject
     * @return custom_element_requirements
     */
    public static function from_array(array $statusobject = null) : custom_element_requirements {
        $res = new custom_element_requirements();

        if (empty($statusobject)) {
            return $res;
        }

        $res->plugins = $statusobject['plugins'];
        $res->libraryconfig = $statusobject['libraryconfig'];

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
        $arr['plugins'] = $this->plugins;
        $arr['libraryconfig'] = $this->libraryconfig;
        return $arr;
    }

    /**
     * @return string[]
     */
    public function get_plugins(): array {
        return $this->plugins;
    }

    /**
     * @param string[] $plugins
     */
    public function add_plugin(string $pluginid, string $pluginversion): void {
        $this->plugins[] = [
            'pluginid' => $pluginid,
            'pluginversion' => $pluginversion
        ];
    }

    /**
     * @return string[]
     */
    public function get_libraryconfig(): array {
        return $this->libraryconfig;
    }

    /**
     * @param string $moduletype
     * @param string $type
     * @param bool $nomodule
     * @throws \coding_exception
     */
    public function set_libraryconfig(string $moduletype, string $type, bool $nomodule = false): void {
        if (!in_array($moduletype, self::MODULE_TYPES)) {
            throw new \coding_exception('Invalid module type: ' . $moduletype);
        }
        $found = false;
        foreach ($this->libraryconfig as $key => $libraryconfig) {
            if ($libraryconfig['moduletype'] === $moduletype) {
                $libraryconfig['type'] = $type;
                $libraryconfig['nomodule'] = $nomodule;
                $this->libraryconfig[$key] = $libraryconfig;
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \coding_exception('Module type not found: ' . $moduletype);
        }
    }

    /**
     * @return array
     * @throws \coding_exception
     */
    public function get_libraryconfig_for_module_type($moduletype): array {
        if (!in_array($moduletype, self::MODULE_TYPES)) {
            throw new \coding_exception('Invalid module type: ' . $moduletype);
        }

        $res = [];
        foreach ($this->libraryconfig as $libraryconfig) {
            if ($libraryconfig['moduletype'] === $moduletype) {
                $res[] = $libraryconfig['type'];
                $res[] = $libraryconfig['nomodule'];
                break;
            }
        }

        if (!empty($res)) {
            return $res;
        }

        throw new \coding_exception('Module type not found: ' . $moduletype);
    }


}
