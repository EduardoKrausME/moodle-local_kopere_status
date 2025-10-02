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
 * Build per-hour uptime rollups for the last 24h and purge raw/old data.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_status\task;

use core\task\scheduled_task;
use Exception;

/**
 * Hourly rollup task.
 */
class hourly_rollup_task extends scheduled_task {
    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     */
    public function get_name(): string {
        return "Hourly rollup task";
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     * @throws Exception
     */
    public function execute() {
        global $DB;

        $interval = get_config("local_kopere_status", "intervalminutes");
        if ($interval <= 0) {
            $interval = 5;
        }
        $expected = floor(60 / $interval); // Expected number of pings per hour.

        // Previous hour in UTC.
        $now = time();
        $hourstart = $now - ($now % 3600) - 3600; // Start of previous hour.
        $y = gmdate("Y", $hourstart);
        $mo = gmdate("n", $hourstart);
        $d = gmdate("j", $hourstart);
        $h = gmdate("G", $hourstart);

        // Aggregate logs for that hour.
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS okcount
                FROM {local_kopere_status_log}
               WHERE year = :y AND month = :mo AND day = :d AND hour = :h";
        $agg = $DB->get_record_sql($sql, ["y" => $y, "mo" => $mo, "d" => $d, "h" => $h]);

        $okcount = ($agg->okcount ?? 0);

        // Missing counts as downtime.
        $effectiveok = $okcount; // Missing are not ok.
        $uptime = 0;
        if ($expected > 0) {
            $uptime = round(($effectiveok / $expected) * 100);
        }
        $uptime = max(0, min(100, $uptime));

        // Upsert into hourly table (unique by y/m/d/h if you add it; here we match manually).
        $select = "year = :y AND month = :mo AND day = :d AND hour = :h";
        $params = ["y" => $y, "mo" => $mo, "d" => $d, "h" => $h];

        if ($row = $DB->get_record_select("local_kopere_status_hourly", $select, $params, "id")) {
            $row->uptime = $uptime;
            $DB->update_record("local_kopere_status_hourly", $row);
        } else {
            $row = (object) [
                "year" => $y,
                "month" => $mo,
                "day" => $d,
                "hour" => $h,
                "uptime" => $uptime,
            ];
            $DB->insert_record("local_kopere_status_hourly", $row);
        }

        // Retention (days) – prune old logs and old rollups by day cutoff (UTC).
        $retentiondays = get_config("local_kopere_status", "retentiondays");
        if ($retentiondays <= 0) {
            $retentiondays = 30;
        }
        $cut = strtotime("-" . $retentiondays . " days", $now);
        $cy = gmdate("Y", $cut);
        $cmo = gmdate("n", $cut);
        $cd = gmdate("j", $cut);

        // Delete logs older than cutoff day (coarse, but cross-DB compatible).
        $DB->delete_records_select("local_kopere_status_log",
            "(year < :cy)
              OR (year = :cy AND month < :cmo)
              OR (year = :cy AND month = :cmo AND day < :cd)",
            ["cy" => $cy, "cmo" => $cmo, "cd" => $cd]);

        // Delete hourly older than cutoff day too.
        $DB->delete_records_select("local_kopere_status_hourly",
            "(year < :cy)
              OR (year = :cy AND month < :cmo)
              OR (year = :cy AND month = :cmo AND day < :cd)",
            ["cy" => $cy, "cmo" => $cmo, "cd" => $cd]);
    }
}
