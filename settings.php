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
 * Plugin administration.
 *
 * @package   local_ce
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN, $CFG;
if (!empty($CFG->local_ce_enable_usage) && $hassiteconfig) {
    $plugin = 'local_ce';

    /** @var admin_root $ADMIN */
    $ADMIN->add(
        'localplugins',
        new admin_category(
            $plugin,
            new lang_string('pluginname', $plugin)
        )
    );

    $settings = new admin_settingpage('local_ce_main', new lang_string('globalsettings', $plugin));

    // Distribution channels.
    $settings->add(new admin_setting_configtextarea("{$plugin}/channels",
        new lang_string('channels', $plugin),
        new lang_string('channels_desc', $plugin),
        ''));

    // Model viewer enabling.
    $settings->add(new admin_setting_configcheckbox("{$plugin}/enablemv",
        new lang_string('enablemv', $plugin),
        new lang_string('enablemv_desc', $plugin),
        '0'));

    $ADMIN->add($plugin, $settings);

    // Custom Elements management page.
    $urlmanagece = new moodle_url('/local/ce/view.php', [
        'controller' => 'admin',
        'action' => 'view'
    ]);
    $ADMIN->add($plugin, new admin_externalpage('adminpage_view',
        new lang_string('adminpageheading', $plugin),
        $urlmanagece->out()
    ));
}
