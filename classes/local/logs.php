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
 * Core checker for HTTP self health and DB test.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_status\local;

use Exception;

/**
 * Core logs and DB test.
 */
class logs {
    /**
     * Return the latest log
     *
     * @return array|false
     * @throws Exception
     */
    private function last_log() {
        global $DB;

        // Get the latest log.
        $log = $DB->get_record_sql("SELECT * FROM {local_kopere_status_log} ORDER BY id DESC LIMIT 1");
        if ($log) {
            $timecreated = strtotime("{$log->year}-{$log->month}-{$log->day} {$log->hour}:{$log->minute}");
            return [
                "id" => $log->id,
                "timecreated" => userdate($timecreated),
                "status" => $log->status,
                "latencyms" => $log->latencyms,
                "httpcode" => $log->httpcode,
                "stale" => 1,
            ];
        } else {
            return false;
        }
    }

    /**
     * Status modules
     *
     * @return array[]
     * @throws Exception
     */
    protected function status_modules() {
        global $SITE;
        $statusmodules = [
            ["name" => $SITE->fullname],
        ];
        $modules = get_config("local_kopere_status", "modules");
        $lines = preg_split('/\r\n|\r|\n/', $modules);
        foreach ($lines as $line) {
            $line = trim($line);
            if (isset($line[3])) {
                $statusmodules[] = ["name" => $line];
            }
        }

        return $statusmodules;
    }

    /**
     * Build the current summary status using last samples and freshness.
     *
     * @return array
     * @throws Exception
     */
    public function summary() {
        $log = $this->last_log();

        // Treat stale or missing data as DOWN to surface outages when no logs exist.
        $overall = $log["status"] ? "operational" : "down";

        $publictitle = get_config("local_kopere_status", "publictitle");
        $pluginname = get_string("pluginname", "local_kopere_status");
        return [
            "title" => isset($publictitle[3]) ? $publictitle : $pluginname,
            "overall" => $overall,
            "overall_label" => get_string("overall_{$overall}", "local_kopere_status"),
            "last_log" => $log,
            "status" => $this->status(),
            "statusmodules" => $this->status_modules(),
        ];
    }

    /**
     * Build status context for status.mustache
     *
     * @return array
     * @throws Exception
     */
    protected function status(): array {
        global $DB;

        $statuspagedays = get_config("local_kopere_status", "statuspagedays");
        if (!$statuspagedays) {
            $statuspagedays = 5;
        }
        $windowhours = $statuspagedays * 24;

        // Build hour bars for the desired window (default 120 hours).
        $hnow = floor(time() / 3600) * 3600;
        $windowhours = max(1, $windowhours);
        $hours = [];
        for ($i = $windowhours; $i >= 1; $i--) {
            $ts = $hnow - ($i * 3600);
            $y = date("Y", $ts);
            $m = date("n", $ts);
            $d = date("j", $ts);
            $h = date("G", $ts);

            $where = ["year" => $y, "month" => $m, "day" => $d, "hour" => $h];
            $row = $DB->get_record("local_kopere_status_hourly", $where, "uptime");

            $pct = $row ? $row->uptime : 0;

            // Map to color class (tweak thresholds to your taste).
            if ($row === false) {
                $cls = "down";
            } else if ($pct >= 99) {
                $cls = "ok";
            } else if ($pct >= 95) {
                $cls = "good";
            } else if ($pct >= 80) {
                $cls = "warn";
            } else if ($pct > 0) {
                $cls = "bad";
            } else {
                $cls = "down";
            }

            $timecreated = strtotime("{$y}-{$m}-{$d} {$h}:{$m}");
            $hours[] = [
                "cls" => $cls,
                "pct" => $pct,
                "timecreated" => userdate($timecreated),
            ];
        }

        return [
            "hours" => $hours,
        ];
    }
}
