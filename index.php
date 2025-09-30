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
 * Status public page.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Public status page (no login).
use local_kopere_status\local\checker;

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/kopere_status/classes/local/checker.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/kopere_status/index.php'));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_config('local_kopere_status', 'publictitle') ?: get_string('pluginname', 'local_kopere_status'));
$PAGE->set_heading(get_config('local_kopere_status', 'publictitle') ?: get_string('pluginname', 'local_kopere_status'));

$c = new checker();
$data = $c->summary();

// Prepare template data.
$tpl = [
    'title' => get_config('local_kopere_status', 'publictitle') ?: get_string('pluginname', 'local_kopere_status'),
    'overall' => $data['overall'],
    'overall_label' => get_string('overall_' . $data['overall'], 'local_kopere_status'),
    'http' => $data['components']['http'] ?? 0,
    'db'   => $data['components']['db'] ?? 0,
    'sample_http' => $data['samples']['http'] ?? null,
    'sample_db'   => $data['samples']['db'] ?? null,
    'jsonurl' => (new moodle_url('/local/kopere_status/status.json.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_kopere_status/status', $tpl);
echo $OUTPUT->footer();
