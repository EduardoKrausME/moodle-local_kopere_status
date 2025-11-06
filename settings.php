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

    $publicurl = new moodle_url("/local/kopere_status/");
    $publiclink = get_string("publiclink", "local_kopere_status");
    $ADMIN->add("localplugins", new admin_externalpage(
        "local_kopere_status_public_1",
        get_string("pluginname", "local_kopere_status")." - {$publiclink}",
        $publicurl
    ));

    $html = "<p>{$publiclink}: <a target='_blank' href='{$publicurl->out(false)}'>{$publicurl->out(false)}</a></p>";
    $settings->add(
        new admin_setting_heading("local_kopere_status_public_2", "", $html)
    );

    $choices = [
        1  => get_string("minute", "local_kopere_status"),
        2  => get_string("minutes", "local_kopere_status", 2),
        3  => get_string("minutes", "local_kopere_status", 3),
        4  => get_string("minutes", "local_kopere_status", 4),
        5  => get_string("minutes", "local_kopere_status", 5),
        6  => get_string("minutes", "local_kopere_status", 6),
        10 => get_string("minutes", "local_kopere_status", 10),
        12 => get_string("minutes", "local_kopere_status", 12),
        15 => get_string("minutes", "local_kopere_status", 15),
        20 => get_string("minutes", "local_kopere_status", 20),
        30 => get_string("minutes", "local_kopere_status", 30),
        59 => get_string("minutes", "local_kopere_status", 60),
    ];
    // Ping interval (minutes).
    $settings->add(new admin_setting_configselect(
        "local_kopere_status/intervalminutes",
        get_string("intervalminutes", "local_kopere_status"),
        get_string("intervalminutes_desc", "local_kopere_status"),
        5,
        $choices
    ));

    $choices = [
        1 => get_string("day", "local_kopere_status"),
        2 => get_string("days", "local_kopere_status", 2),
        3 => get_string("days", "local_kopere_status", 3),
        4 => get_string("days", "local_kopere_status", 4),
        5 => get_string("days", "local_kopere_status", 5),
        6 => get_string("days", "local_kopere_status", 6),
        7 => get_string("days", "local_kopere_status", 7),
    ];
    // Status page days.
    $settings->add(new admin_setting_configselect(
        "local_kopere_status/statuspagedays",
        get_string("statuspagedays", "local_kopere_status"),
        get_string("statuspagedays_desc", "local_kopere_status"),
        5,
        $choices
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
        ""
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
