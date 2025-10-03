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
 * phpcs:disable moodle.Files.RequireLogin.Missing
 * Status public page.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_kopere_status\local\logs;

require(__DIR__ . "/../../config.php");

$PAGE->set_url(new moodle_url("/local/kopere_status/index.php"));
$PAGE->set_pagelayout("embedded");
$PAGE->set_title(get_config("local_kopere_status", "publictitle") ?: get_string("pluginname", "local_kopere_status"));
$PAGE->set_heading(get_config("local_kopere_status", "publictitle") ?: get_string("pluginname", "local_kopere_status"));

// Prepare template data.
$c = new logs();
$templatedata = $c->summary(); // 5 days.

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_kopere_status/status", $templatedata);
echo $OUTPUT->footer();
