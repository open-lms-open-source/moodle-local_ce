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
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ce\model;

use context_system;
use local_ce\api\custom_element_parameters;
use local_ce\api\custom_element_requirements;
use \core\url;

defined ('MOODLE_INTERNAL') || die();

/**
 * Custom element model.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_element extends abstract_model {

    const CETYPE_MANUAL = 1;

    const CETYPE_CHANNEL = 2;

    const CE_ICON_CIRCLE = 'circle';
    const CE_ICON_TRIANGLE = 'triangle';
    const CE_ICON_DIAMOND = 'diamond';
    const CE_ICON_SQUARE = 'square';
    const CE_ICON_PENTAGON = 'pentagon';

    const CE_DEFAULT_ICON = self::CE_ICON_CIRCLE;

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
     * @var custom_element_requirements
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
     * @var string
     */
    public $iconurl;

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
                                custom_element_parameters $parameters, custom_element_requirements $requirements,
                                $timemodified, $version, $cdnurl, $cdnurles5) {
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
            $murl = new url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'editce',
                'ceid' => $this->id
            ]);
            $this->editurl = $murl->out(false);

            $murl = new url('/local/ce/view.php', [
                'controller' => 'admin',
                'action' => 'deletece',
                'ceid' => $this->id
            ]);
            $this->deleteurl = $murl->out(false);

            $this->iconurl = $this->get_icon_file_url();
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
        $record->parameters = $this->parameters->to_json_string();
        $record->requirements = $this->requirements->to_json_string();
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
        $errors = [];

        // CE name validations.
        if (empty($this->name)) {
            $errors['name'] = get_string('ce_form_error_name_empty', 'local_ce');
        }

        // CE cename validations.
        if (empty($this->cename)) {
            $errors['cename'] = get_string('ce_form_error_cename_empty', 'local_ce');
        }

        $matches = [];
        preg_match('/[a-z]+-[a-z\\-]+/',$this->cename,$matches);
        if (empty($matches)) {
            $errors['cename'] = get_string('ce_form_error_cename_lettershyphens', 'local_ce');
        }

        $matches = [];
        preg_match('/[A-Z]+/',$this->cename,$matches);
        if (!empty($matches)) {
            $errors['cename'] = get_string('ce_form_error_cename_nouppercase', 'local_ce');
        }

        // CE version validations.
        if (empty($this->version)) {
            $errors['version'] = get_string('ce_form_error_version_empty', 'local_ce');
        }

        // CE version validations.
        if (is_int($this->version) && $this->version < 0) {
            $errors['version'] = get_string('ce_form_error_version_notint', 'local_ce');
        }

        return $errors;
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
            custom_element_parameters::from_string($record->parameters),
            custom_element_requirements::from_string($record->requirements),
            $record->timemodified,
            $record->version,
            $record->cdnurl,
            $record->cdnurles5
        );
    }

    /**
     * @return string|null
     * @throws \core\exception\coding_exception
     * @throws \dml_exception
     */
    public function get_icon_file_url() {
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, 'local_ce', 'icon', $this->id);
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if ('.' === $file->get_filename()) {
                continue;
            }
            $url = url::make_pluginfile_url($file->get_contextid(), 'local_ce', 'icon',
                $file->get_itemid(), $file->get_filepath(), $filename)->out(false);
            return $url;
        }
        return null;
    }
}
