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

namespace local_statusboard\task;

use Exception;

/**
 * Build per-hour uptime rollups for the last 24h and purge raw/old data.
 *
 * Strategy:
 * - For each fully elapsed hour in the last 24h:
 *   - Compute expected sample "buckets" using intervalminutes.
 *   - For each bucket, mark OK if (at least one HTTP UP) AND (at least one DB UP) in that bucket.
 *   - Missing buckets => DOWN (covers periods when Moodle/server/db were OFF).
 *   - Store one row per hour in local_statusboard_hourly (upsert).
 * - After processing, delete raw logs for hours already rolled-up (keep only current hour).
 * - Purge hourly rollups older than retentiondays.
 */
class hourly_rollup_task extends \core\task\scheduled_task {

    /**
     * Get_name
     *
     * @return string
     * @throws Exception
     */
    public function get_name(): string {
        return get_string('task_hourly_rollup', 'local_statusboard');
    }

    /**
     * execute
     *
     * @return void
     * @throws Exception
     */
    public function execute(): void {
        $enabled = (int) get_config('local_statusboard', 'enabled');
        if (!$enabled) {
            mtrace('local_statusboard: hourly rollup skipped (disabled).');
            return;
        }

        $intervalmin = max(1, (int) get_config('local_statusboard', 'intervalminutes'));
        $retentiondays = max(1, (int) get_config('local_statusboard', 'retentiondays'));

        $intervalsec = $intervalmin * 60;
        $now = time();
        $currenthourstart = (int) floor($now / 3600) * 3600;

        // Process last 24 fully closed hours (exclude current hour).
        $hours = [];
        for ($h = 1; $h <= 24; $h++) {
            $hours[] = $currenthourstart - ($h * 3600);
        }

        foreach ($hours as $hourstart) {
            $this->rollup_hour($hourstart, $intervalsec);
        }

        // Purge raw logs for hours already rolled-up (older than current hour).
        $this->purge_raw_logs($currenthourstart);

        // Purge hourly rollups older than retentiondays.
        $this->purge_hourly_rollups($retentiondays);
    }

    /**
     * Rollup hour
     *
     * @param int $hourstart
     * @param int $intervalsec
     * @return void
     * @throws Exception
     */
    protected function rollup_hour(int $hourstart, int $intervalsec): void {
        global $DB;

        $hourend = $hourstart + 3600;
        $expected = max(1, (int) floor(3600 / $intervalsec)); // E.g. 12 for 5min.

        // Prepare bucket arrays.
        $httpok = array_fill(0, $expected, false);
        $dbok = array_fill(0, $expected, false);

        // Fetch logs inside hour window.
        $sql = "SELECT timecreated, type, status
                  FROM {local_statusboard_log}
                 WHERE timecreated >= :start AND timecreated < :end";
        $params = ['start' => $hourstart, 'end' => $hourend];
        $logs = $DB->get_records_sql($sql, $params);

        foreach ($logs as $log) {
            $offset = (int) $log->timecreated - $hourstart;
            if ($offset < 0 || $offset >= 3600) {
                continue;
            }
            $bucket = (int) floor($offset / $intervalsec);
            if ($bucket < 0 || $bucket >= $expected) {
                continue;
            }
            $isup = ((int) $log->status === 1);
            if ($log->type === 'http') {
                $httpok[$bucket] = $httpok[$bucket] || $isup;
            } else if ($log->type === 'db') {
                $dbok[$bucket] = $dbok[$bucket] || $isup;
            }
        }

        $okcount = 0;
        for ($i = 0; $i < $expected; $i++) {
            if ($httpok[$i] && $dbok[$i]) {
                $okcount++;
            }
        }

        $uptime = round(($okcount / $expected) * 100, 2);

        // Upsert into local_statusboard_hourly.
        $existing = $DB->get_record('local_statusboard_hourly', ['hourstart' => $hourstart], '*', IGNORE_MISSING);

        $row = new \stdClass();
        $row->hourstart = $hourstart;
        $row->samplesexpected = $expected;
        $row->samplesok = $okcount;
        $row->uptime = $uptime;
        $row->timemodified = time();

        if ($existing) {
            $row->id = $existing->id;
            $DB->update_record('local_statusboard_hourly', $row);
        } else {
            $row->timecreated = $row->timemodified;
            $DB->insert_record('local_statusboard_hourly', $row);
        }

        mtrace("local_statusboard: rollup {$hourstart} => {$uptime}% ({$okcount}/{$expected})");
    }

    /**
     * Purge logs
     *
     * @param int $currenthourstart
     * @return void
     * @throws Exception
     */
    protected function purge_raw_logs(int $currenthourstart): void {
        global $DB;
        // Remove raw logs older than the start of the current hour (i.e., all that we could have rolled up).
        $deleted = $DB->delete_records_select('local_statusboard_log', 'timecreated < :cut', ['cut' => $currenthourstart]);
        mtrace("local_statusboard: purged raw logs <= " . ($currenthourstart - 1) . " (deleted={$deleted})");
    }

    /**
     * Purge rollups
     *
     * @param int $retentiondays
     * @return void
     * @throws Exception
     */
    protected function purge_hourly_rollups(int $retentiondays): void {
        global $DB;
        $cut = time() - ($retentiondays * 86400);
        // Align cut to hour start for consistency (optional).
        $cut = (int) floor($cut / 3600) * 3600;

        $deleted = $DB->delete_records_select('local_statusboard_hourly', 'hourstart < :cut', ['cut' => $cut]);
        mtrace("local_statusboard: purged hourly rollups < {$cut} (deleted={$deleted})");
    }
}
