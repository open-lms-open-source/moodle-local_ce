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
 * Default controller
 *
 * @package   local_ce
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

use local_ce\api\custom_element_requirements;
use local_ce\form\instance_form;
use local_ce\model\custom_element;
use local_ce\model\instance;
use local_ce\model\set;
use local_ce\form\custom_element_form;
use local_ce\form\set_form;
use local_ce\model\set_course;

/**
 * Default controller
 *
 * @package   local_ce
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
class local_ce_controller_admin extends mr_controller_admin {

    /**
     * Plugin identifier.
     */
    const PLUGIN = 'local_ce';

    private $taboutput;

    /**
     * Set heading
     */
    protected function init() {
        $this->heading->set('adminpageheading');
    }

    /**
     *
     */
    public function admin_setup() {
        admin_externalpage_setup('adminpage_view');
        $action = optional_param('action', 'listces', PARAM_TEXT);
    }

    /**
     * Default screen.
     */
    public function view_action() {
        return $this->listces_action();
    }

    public function listces_action() {
        $this->taboutput = $this->render_ce_admin_tabs('listces');

        $newurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'editce',
        ]);

        $saved = optional_param('saved', 0, PARAM_INT);

        $ces = array_values(custom_element::get_all()); // This resets indexes.
        return $this->taboutput . $this->output->render_from_template('local_ce/ce_list',
            (object)[
                'ces' => $ces,
                'newurl' => $newurl->out(false),
            ]);
    }

    public function editce_action() {
        global $PAGE;

        $this->taboutput = $this->render_ce_admin_tabs('listces');

        $ceid = optional_param('ceid', null, PARAM_INT);

        $listcesmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listces',
        ]);
        $listcesurl = $listcesmurl->out(false);
        $listcesstr = get_string('admin_listces', 'local_ce');

        $edittitle = !empty($ceid) ? get_string('admin_editce', 'local_ce') : get_string('admin_newce', 'local_ce');

        $output = <<<HTML
<div class="container-fluid">
    <div class="row">
        <div class="col d-flex w-100 justify-content-between">
            <h3>
                $edittitle
            </h3>
            <h2>
                <a href="$listcesurl">$listcesstr</a>
            </h2>
        </div>
    </div>
</div>
HTML;
        $submitparams = [
            'controller' => 'admin',
            'action' => 'editce',
        ];
        if (!empty($ceid)) {
            $submitparams['ceid'] = $ceid;
        }
        $submiturl = new moodle_url('/local/ce/view.php', $submitparams);
        $mform = new custom_element_form($submiturl);

        $context = context_system::instance();
        $toform = new \stdClass();

        $draftitemid = file_get_submitted_draft_itemid('iconfileid');
        file_prepare_draft_area($draftitemid, $context->id, 'local_ce', 'icon',
            $ceid, $mform->get_iconfile_options());
        $toform->iconfileid = $draftitemid;

        foreach (custom_element_requirements::MODULE_TYPES as $modtype) {
            $draftitemid = file_get_submitted_draft_itemid($modtype . 'fileid');
            file_prepare_draft_area($draftitemid, $context->id, 'local_ce', $modtype,
                $ceid, $mform->get_modulefile_options());
            $toform->{$modtype . 'fileid'} = $draftitemid;
        }

        // The list of plugins will be used in several parts, preloading it here.
        $pluginopts = $mform->get_plugin_list();
        if (!empty($ceid)) {
            /** @var custom_element $ce */
            $ce = custom_element::get_by_id($ceid);
            $toform = (object) array_merge((array) $ce, (array) $toform);
            $toform->parameters = $ce->parameters->to_json_string();
            /** @var custom_element_requirements $requirements */
            $requirements = $ce->requirements;
            $plugins = $requirements->get_plugins();
            $toform->requiredplugins = [];
            foreach ($plugins as $plugin) {
                $toform->requiredplugins[] = array_search(
                    implode(',', [$plugin['pluginid'] , $plugin['pluginversion']]),
                    $pluginopts);
            }

            foreach (custom_element_requirements::MODULE_TYPES as $modtype) {
                list(
                    $toform->{$modtype . '_script_type'},
                    $toform->{$modtype . '_script_nomodule'},
                ) = $requirements->get_libraryconfig_for_module_type($modtype);

                $toform->{$modtype . '_script_type'} = array_search(
                    $toform->{$modtype . '_script_type'}, custom_element_requirements::SCRIPT_TYPES);
            }
        }
        $mform->set_data($toform);

        if ($fromform = $mform->get_data()) {
            $ce = $mform->create_object_from_form_data($ceid, $fromform);

            $ce->save();

            // Process form submission files.
            file_save_draft_area_files($fromform->iconfileid, $context->id, 'local_ce', 'icon',
                $ce->id, $mform->get_iconfile_options());
            foreach (custom_element_requirements::MODULE_TYPES as $modtype) {
                file_save_draft_area_files($fromform->{$modtype . 'fileid'}, $context->id, 'local_ce', $modtype,
                    $ce->id, $mform->get_modulefile_options());
            }

            $this->url->param('controller', 'admin');
            $this->url->param('action', 'listces');
            $this->url->param('saved', 1);

            redirect($this->url);
        }

        $output .= $mform->render();
        return $this->taboutput . $this->output->box($output, 'boxwidthwide');
    }

    public function deletece_action() {
        $ceid = required_param('ceid', PARAM_INT);
        $ce = custom_element::get_by_id($ceid);
        $ce->delete();

        $this->url->param('controller', 'admin');
        $this->url->param('action', 'listces');
        $this->url->param('deleted', 1);

        redirect($this->url);
    }

    public function listsets_action() {

        $this->taboutput = $this->render_ce_admin_tabs('listsets');
        $newurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'editset',
        ]);

        $saved = optional_param('saved', 0, PARAM_INT);

        $sets = array_values(set::get_all()); // This resets indexes.
        return $this->taboutput . $this->output->render_from_template('local_ce/set_list',
            (object)[
                'sets' => $sets,
                'newurl' => $newurl->out(false),
            ]);
    }

    public function editset_action() {
        global $PAGE;

        $this->taboutput = $this->render_ce_admin_tabs('listsets');

        $setid = optional_param('setid', null, PARAM_INT);

        $listsetsmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listsets',
        ]);
        $listsetsurl = $listsetsmurl->out(false);
        $listsetsstr = get_string('admin_listsets', 'local_ce');

        $edittitle = !empty($setid) ? get_string('admin_editset', 'local_ce') : get_string('admin_newset', 'local_ce');

        $output = <<<HTML
<div class="container-fluid">
    <div class="row">
        <div class="col d-flex w-100 justify-content-between">
            <h3>
                $edittitle
            </h3>
            <h2>
                <a href="$listsetsurl">$listsetsstr</a>
            </h2>
        </div>
    </div>
</div>
HTML;
        $submitparams = [
            'controller' => 'admin',
            'action' => 'editset',
        ];
        if (!empty($setid)) {
            $submitparams['setid'] = $setid;
        }
        $submiturl = new moodle_url('/local/ce/view.php', $submitparams);
        $mform = new set_form($submiturl);

        $context = $PAGE->context;
        $toform = new \stdClass();

        $draftitemid = file_get_submitted_draft_itemid('iconfileid');
        file_prepare_draft_area($draftitemid, $context->id, 'local_ce', 'icon_set',
            $setid, $mform->get_iconfile_options());
        $toform->iconfileid = $draftitemid;

        if (!empty($setid)) {
            $set = set::get_by_id($setid);

            $setcourses = set_course::get_all_by_setid($setid);
            $courseidsparams = [
                'courseids' => array_map(function($setcourse) {
                    return $setcourse->courseid;
                }, $setcourses),
            ];

            $mform->set_data((object)array_merge((array)$set, (array)$toform, $courseidsparams));
        }

        if ($fromform = $mform->get_data()) {
            // Process form submission.
            $cap = !empty($fromform->requiredcapability) ? $fromform->requiredcapability : null;
            $set = new set(
                $setid,
                $fromform->name,
                $fromform->status,
                $fromform->defaulticon,
                $cap
            );

            $set->save();

            set_course::sync_set_with_courses($set, $fromform->courseids);

            // Process form submission files.
            file_save_draft_area_files($fromform->iconfileid, $context->id, 'local_ce', 'icon_set',
                $set->id, $mform->get_iconfile_options());

            $this->url->param('controller', 'admin');
            $this->url->param('action', 'listsets');
            $this->url->param('saved', 1);

            redirect($this->url);
        }

        $output .= $mform->render();
        return $this->taboutput . $this->output->box($output, 'boxwidthwide');
    }

    public function deleteset_action() {
        $setid = required_param('setid', PARAM_INT);
        $set = set::get_by_id($setid);
        $set->delete();

        $this->url->param('controller', 'admin');
        $this->url->param('action', 'listsets');
        $this->url->param('deleted', 1);

        redirect($this->url);
    }

    public function listinstances_action() {
        $this->taboutput = $this->render_ce_admin_tabs('listsets');
        $setid = required_param('setid', PARAM_INT);

        $newurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'editinstance',
            'setid' => $setid,
        ]);

        $saved = optional_param('saved', 0, PARAM_INT);

        $listsetsmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listsets',
        ]);
        $listsetsurl = $listsetsmurl->out(false);

        $instances = array_values(instance::get_all_by_setid($setid)); // This resets indexes.
        $setname = set::get_by_id($setid)->name;
        return $this->taboutput . $this->output->render_from_template('local_ce/instance_list',
            (object)[
                'instances' => $instances,
                'newurl' => $newurl->out(false),
                'setname' => $setname,
                'listsetsurl' => $listsetsurl,
            ]);
    }

    public function editinstance_action() {
        global $PAGE;

        $this->taboutput = $this->render_ce_admin_tabs('listsets');

        $setid = required_param('setid', PARAM_INT);

        $instanceid = optional_param('instanceid', null, PARAM_INT);

        $listinstancessmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listinstances',
            'setid' => $setid,
        ]);
        $listinstancesurl = $listinstancessmurl->out(false);
        $listinstancesstr = get_string('admin_listinstances', 'local_ce');

        $edittitle = !empty($instanceid) ? get_string('admin_editinstance', 'local_ce') : get_string('admin_newinstance', 'local_ce');

        $output = <<<HTML
<div class="container-fluid">
    <div class="row">
        <div class="col d-flex w-100 justify-content-between">
            <h3>
                $edittitle
            </h3>
            <h2>
                <a href="$listinstancesurl">$listinstancesstr</a>
            </h2>
        </div>
    </div>
</div>
HTML;
        $submitparams = [
            'controller' => 'admin',
            'action' => 'editinstance',
            'setid' => $setid,
        ];
        if (!empty($instanceid)) {
            $submitparams['instanceid'] = $instanceid;
        }
        $submiturl = new moodle_url('/local/ce/view.php', $submitparams);
        $mform = new instance_form($submiturl);

        $context = $PAGE->context;
        $toform = new \stdClass();

        if (!empty($instanceid)) {
            $instance = instance::get_by_id($instanceid);
            $mform->set_data((object)array_merge((array)$instance, (array)$toform));
        }

        if ($fromform = $mform->get_data()) {
            // Process form submission.
            $instance = new instance(
                $instanceid,
                $fromform->customname,
                $fromform->customelementid,
                $setid,
                $fromform->config,
                null
            );

            $instance->save();

            $this->url->param('controller', 'admin');
            $this->url->param('action', 'listinstances');
            $this->url->param('setid', $setid);
            $this->url->param('saved', 1);

            redirect($this->url);
        }

        $output .= $mform->render();
        return $this->taboutput . $this->output->box($output, 'boxwidthwide');
    }

    public function deleteinstance_action() {
        $setid = required_param('setid', PARAM_INT);
        $instanceidid = required_param('instanceid', PARAM_INT);
        $instance = instance::get_by_id($instanceidid);
        $instance->delete();

        $this->url->param('controller', 'admin');
        $this->url->param('action', 'listinstances');
        $this->url->param('setid', $setid);
        $this->url->param('deleted', 1);

        redirect($this->url);
    }

    private function render_ce_admin_tabs($action) {
        global $OUTPUT;
        $listcesmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listces',
        ]);
        $listcesurl = $listcesmurl->out(false);
        $listcesstr = get_string('admin_listces', 'local_ce');

        $listsetsmurl = new moodle_url('/local/ce/view.php', [
            'controller' => 'admin',
            'action' => 'listsets',
        ]);
        $listsetsurl = $listsetsmurl->out(false);
        $listsetsstr = get_string('admin_listsets', 'local_ce');

        return $OUTPUT->render_from_template('local_ce/tabs',
            (object)[
                'tabs' => [
                    (object) [
                        'activeclass' => $action === 'listces' ? 'active' : '',
                        'url' => $listcesurl,
                        'label' => $listcesstr,
                    ],
                    (object) [
                        'activeclass' => $action === 'listsets' ? 'active' : '',
                        'url' => $listsetsurl,
                        'label' => $listsetsstr,
                    ],
                ],
            ]);
    }
}
