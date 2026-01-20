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
 * Web components loader class.
 *
 * Loads registered web components.
 *
 * @package   local_ce
 * @author    David Castro
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 */

namespace local_ce;

defined('MOODLE_INTERNAL') || die();

/**
 * Web components loader class.
 *
 * Loads registered web components.
 *
 * @package   local_ce
 * @author    David Castro
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 */
class ce_loader {

    /**
     * @var ce_loader
     */
    private static $instance;

    /**
     * @var array
     */
    private $components;

    private function __construct() {
        $this->components = [];
    }

    public static function get_instance(): ce_loader {
        if (is_null(self::$instance)) {
            self::$instance = new ce_loader();
        }
        return self::$instance;
    }

    /**
     * @param string $componentid
     * @param string $src
     * @param string $type
     */
    public function register_component(string $componentid, string $src, string $type = 'module') {
        $this->components[$componentid] = [
            'src' => $src,
            'type' => $type,
        ];
    }

    /**
     * @return false|string
     */
    public function get_components_json() {
        return json_encode($this->components);
    }

    /**
     * Loads the web components the RequireJS module.
     */
    public function load_components() {
        global $PAGE;

        $PAGE->requires->js_call_amd('local_ce/wcloader', 'init', [
            'componentsJson' => $this->get_components_json(),
        ]);
    }
}
