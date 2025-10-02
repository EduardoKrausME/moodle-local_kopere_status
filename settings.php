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
 * Settings file.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage("local_kopere_status",
        get_string("pluginname", "local_kopere_status"));

    // Ping interval (minutes).
    $settings->add(new admin_setting_configtext(
        "local_kopere_status/intervalminutes",
        get_string("intervalminutes", "local_kopere_status"),
        get_string("intervalminutes_desc", "local_kopere_status"),
        5,
        PARAM_INT
    ));

    // Retention (days) for hourly rollups (and general cleanup).
    $settings->add(new admin_setting_configtext(
        "local_kopere_status/retentiondays",
        get_string("retentiondays", "local_kopere_status"),
        get_string("retentiondays_desc", "local_kopere_status"),
        30,
        PARAM_INT
    ));

    // Title shown on public page.
    $settings->add(new admin_setting_configtext(
        "local_kopere_status/publictitle",
        get_string("publictitle", "local_kopere_status"),
        get_string("publictitle_desc", "local_kopere_status"),
        "System Status"
    ));

    // Title shown on public page.
    $settings->add(new admin_setting_configtextarea(
        "local_kopere_status/modules",
        get_string("modules", "local_kopere_status"),
        get_string("modules_desc", "local_kopere_status"),
        ""
    ));

    $ADMIN->add("localplugins", $settings);
}
