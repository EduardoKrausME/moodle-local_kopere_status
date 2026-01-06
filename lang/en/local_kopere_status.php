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
 * En lang file.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['day'] = '1 day';
$string['days'] = '{$a} days';
$string['down'] = 'Down';
$string['intervalminutes'] = 'Check interval (minutes)';
$string['intervalminutes_desc'] = 'Minimum time between check cycles. The scheduled task will gate itself by this value.';
$string['lastcheck'] = 'Last check';
$string['minute'] = '1 minute';
$string['minutes'] = '{$a} minutes';
$string['modules'] = 'Modules';
$string['modules_desc'] = 'Enter one module per line. Each line will be replicated for the different statuses. E.g., if you enter "Enrollment" and "Support" (one per line), the statuses will be displayed only for "Enrollment" and for "Support".';
$string['nodata'] = 'No data yet';
$string['overall_down'] = 'System unavailable';
$string['overall_operational'] = 'All systems operational';
$string['privacy:metadata'] = 'The Kopere Status plugin does not store any personal data.';
$string['pluginname'] = 'System Status';
$string['publiclink'] = 'Public status page';
$string['publictitle'] = 'Public title';
$string['publictitle_desc'] = 'Title displayed on the public status page.';
$string['retentiondays'] = 'Retention (days)';
$string['retentiondays_desc'] = 'Delete per-hour rollup rows older than this many days. Raw logs are also purged after each rollup (keep only current hour for next aggregation).';
$string['statuspagedays'] = 'Status page days';
$string['statuspagedays_desc'] = 'Number of days to display in the status page (choose from 1 to 7).';
$string['task_hourly_rollup'] = 'StatusBoard hourly rollup & cleanup';
$string['up'] = 'Up';
