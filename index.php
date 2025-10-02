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
// Public status page (no login).
use local_kopere_status\local\logs;

require(__DIR__ . "/../../config.php");

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url("/local/kopere_status/index.php"));
$PAGE->set_pagelayout("embedded");
$PAGE->set_title(get_config("local_kopere_status", "publictitle") ?: get_string("pluginname", "local_kopere_status"));
$PAGE->set_heading(get_config("local_kopere_status", "publictitle") ?: get_string("pluginname", "local_kopere_status"));

$statusmodules = [
    ["name" => $SITE->fullname],
];
$modules = get_config('local_kopere_status', 'modules');
$lines = preg_split('/\r\n|\r|\n/', $modules);
foreach ($lines as $line) {
    $line = trim($line);
    if (isset($line[3])) {
        $statusmodules[] = ["name" => $line];
    }
}

// Prepare template data.
$c = new logs();
$summary = $c->summary();
$tpl = [
    "title" => get_config("local_kopere_status", "publictitle") ?: get_string("pluginname", "local_kopere_status"),
    "overall" => $summary["overall"],
    "overall_label" => get_string("overall_" . $summary["overall"], "local_kopere_status"),
    "http" => $summary["components"] ?? 0,
    "sample_http" => $summary["samples"] ?? null,
    "statusmodules" => $statusmodules,
    "status" => $c->status(120),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_kopere_status/status", $tpl);
echo $OUTPUT->footer();
