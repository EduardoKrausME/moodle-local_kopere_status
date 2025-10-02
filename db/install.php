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
 * Post-install hook for local_kopere_status.
 * Seeds last 168 hours (UTC) with 100% uptime to start with a green chart.
 *
 *
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   local_kopere_status
 */

/**
 * Install
 *
 * @throws Exception
 */
function xmldb_local_kopere_status_install() {
    global $DB;

    $hours = 168;
    $now = time();

    $records = [];
    for ($i = $hours; $i >= 1; $i--) {
        $t = $now - ($now % 3600) - ($i * 3600);
        $rec = (object) [
            "year" => gmdate("Y", $t),
            "month" => gmdate("n", $t),
            "day" => gmdate("j", $t),
            "hour" => gmdate("G", $t),
            "uptime" => 100,
        ];
        $records[] = $rec;
    }

    // Insert in small batches to avoid lock contention on some DBs.
    foreach (array_chunk($records, 50) as $chunk) {
        foreach ($chunk as $hourly) {
            // If already exists, skip.
            $where = [
                "year" => $hourly->year,
                "month" => $hourly->month,
                "day"  => $hourly->day,
                "hour"  => $hourly->hour,
            ];
            $exists = $DB->record_exists("local_kopere_status_hourly", $where);
            if (!$exists) {
                $DB->insert_record("local_kopere_status_hourly", $hourly);
            }
        }
    }
}
