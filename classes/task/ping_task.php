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
 * Scheduled task: performs checks respecting configured interval.
 *
 * @package   local_kopere_status
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_kopere_status\task;

use core\task\scheduled_task;
use curl;
use Exception;
use moodle_url;

/**
 * Ping task
 */
class ping_task extends scheduled_task {
    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     */
    public function get_name(): string {
        return "Status ping task";
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     * @throws Exception
     */
    public function execute() {
        global $DB, $CFG;

        require_once($CFG->libdir . "/filelib.php");

        // Read interval (minutes). Default to 5 if not set.
        $interval = get_config("local_kopere_status", "intervalminutes");
        if ($interval <= 0) {
            $interval = 5;
        }

        // Only act on minutes that are multiples of the interval.
        $now = time();
        $currentminute = gmdate("i", $now);
        if ($interval > 1 && ($currentminute % $interval) !== 0) {
            return;
        }

        // Build time components in UTC.
        $minute = gmdate("i", $now);
        $hour = gmdate("G", $now);
        $day = gmdate("j", $now);
        $month = gmdate("n", $now);
        $year = gmdate("Y", $now);

        // Prepare request to our health endpoint.
        $url = new moodle_url("/local/kopere_status/health.php");

        $curl = new curl();
        $curl->setopt([
            "CURLOPT_CERTINFO" => 0,
            "CURLOPT_SSL_VERIFYPEER" => false,
            "CURLOPT_CONNECTTIMEOUT" => 3000,
            "CURLOPT_TIMEOUT" => 3000,
            "CURLOPT_FOLLOWLOCATION" => 0,
            "CURLOPT_RETURNTRANSFER" => true,
            "CURLOPT_NOBODY" => false,
            "CURLOPT_USERAGENT" =>
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 StatusMoodle/{$CFG->release}",
        ]);

        $t0 = microtime(true);
        $content = $curl->get($url->out(false));
        $latencyms = round((microtime(true) - $t0) * 1000);

        $info = $curl->get_info();
        $httpcode = ($info["http_code"] ?? 0);

        $ok = false;
        if ($httpcode === 200 && $content !== false && $content !== "") {
            $json = json_decode($content, true);
            $ok = !empty($json["ok"]);
        }

        $status = $ok ? 1 : 0;

        // Upsert by unique (minute,hour,day,month,year).
        $select = "minute = :m AND hour = :h AND day = :d AND month = :mo AND year = :y";
        $params = ["m" => $minute, "h" => $hour, "d" => $day, "mo" => $month, "y" => $year];

        if ($existing = $DB->get_record_select("local_kopere_status_log", $select, $params, "id")) {
            $existing->status = $status;
            $existing->latencyms = $latencyms;
            $existing->httpcode = $httpcode;
            $DB->update_record("local_kopere_status_log", $existing);
        } else {
            $log = (object) [
                "minute" => $minute,
                "hour" => $hour,
                "day" => $day,
                "month" => $month,
                "year" => $year,
                "status" => $status,
                "latencyms" => $latencyms,
                "httpcode" => $httpcode,
            ];
            try {
                $DB->insert_record("local_kopere_status_log", $log);
            } catch (\dml_write_exception $e) {
                // If another cron wrote the same minute, try to update instead.
                if ($existing = $DB->get_record_select("local_kopere_status_log", $select, $params, "id")) {
                    $existing->status = $status;
                    $existing->latencyms = $latencyms;
                    $existing->httpcode = $httpcode;
                    $DB->update_record("local_kopere_status_log", $existing);
                }
            }
        }
    }
}
