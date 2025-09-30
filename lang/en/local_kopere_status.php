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
 * En file.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['component_db'] = 'Database';
$string['component_http'] = 'Application HTTP';
$string['down'] = 'Down';
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'Enable/disable all checks and the public page.';
$string['healthtoken'] = 'Health token (optional)';
$string['healthtoken_desc'] = 'If set, the /local/kopere_status/health.php endpoint requires &token=...';
$string['intervalminutes'] = 'Check interval (minutes)';
$string['intervalminutes_desc'] = 'Minimum time between check cycles. The scheduled task will gate itself by this value.';
$string['lastcheck'] = 'Last check';
$string['nodata'] = 'No data yet';
$string['overall_down'] = 'Major outage';
$string['overall_operational'] = 'All systems operational';
$string['overall_partial'] = 'Partial outage';
$string['pluginname'] = 'Status Board';
$string['publictitle'] = 'Public title';
$string['publictitle_desc'] = 'Title displayed on the public status page.';
$string['retentiondays'] = 'Retention (days)';
$string['retentiondays_desc'] = 'Delete per-hour rollup rows older than this many days. Raw logs are also purged after each rollup (keep only current hour for next aggregation).';
$string['task_hourly_rollup'] = 'StatusBoard hourly rollup & cleanup';
$string['up'] = 'Up';
$string['viewjson'] = 'View JSON';
