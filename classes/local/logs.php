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
     * Return the latest samples for 'http' and 'db'.
     *
     * Rules:
     * - "Freshness" threshold = 2 * intervalminutes.
     * - If no recent sample exists (e.g., logs purged or service OFF), we synthesize a
     *   "stale" sample with status=0 (DOWN) so the UI reflects downtime in absence of data.
     *
     * @return array
     * @throws Exception
     */
    private function last_samples() {
        global $DB;

        // Get the most recent log.
        $recs = $DB->get_records_sql(
            "SELECT *
                   FROM {local_kopere_status_log}
               ORDER BY id DESC", [], 0, 1
        );
        $rec = $recs ? reset($recs) : null;

        if ($rec) {
            return [
                "id" => $rec->id,
                "timecreated" => "{$rec->day}/{$rec->month}/{$rec->year} {$rec->hour}:{$rec->minute}",
                "status" => $rec->status,
                "latencyms" => $rec->latencyms,
                "httpcode" => $rec->httpcode,
                "fresh" => 1,
                "stale" => 1,
            ];
        } else {
            return [
                "id" => 0,
                "timecreated" => userdate(time()),
                "status" => 0,
                "latencyms" => 0,
                "httpcode" => "",
                "fresh" => 0,
                "stale" => 0,
            ];
        }
    }

    /**
     * Build the current summary status using last samples and freshness.
     *
     * @return array
     * @throws Exception
     */
    public function summary() {
        $samples = $this->last_samples();

        // Treat stale or missing data as DOWN to surface outages when no logs exist.
        $httpok = ($samples["fresh"] == 1 && $samples["status"] == 1) ? 1 : 0;
        $overall = $httpok ? "operational" : "down";

        return [
            "overall" => $overall,
            "components" => $httpok,
            "samples" => $samples,
        ];
    }

    /**
     * Build status context for status.mustache: overall, uptime24h and the last N hour bars.
     *
     * - Uses local_kopere_status_hourly (year,month,day,hour,uptime int 0..100).
     * - Missing hours are treated as 0% (DOWN) to reflect outage/offline windows.
     * - Window size defaults to 120 hours (≈5 dias), adjustable via parameter.
     *
     * @param int $windowhours Number of trailing hours to display (default 120).
     * @return array
     * @throws Exception
     */
    public function status(int $windowhours = 120): array {
        global $DB;

        // 1) Overall from current summary.
        $summary = $this->summary();
        $overall = $summary['overall'];
        $overalllabel = get_string('overall_' . $overall, 'local_kopere_status');

        // 2) Title and 24h uptime.
        $title = (string)get_config('local_kopere_status', 'publictitle');
        if ($title === '') {
            $title = get_string('pluginname', 'local_kopere_status');
        }

        // Compute last 24h mean uptime using hourly table (down if missing).
        $hnow = (int)floor(time() / 3600) * 3600;
        $last24 = [];
        for ($i = 1; $i <= 24; $i++) {
            $ts = $hnow - ($i * 3600);
            $y = (int)gmdate('Y', $ts);
            $m = (int)gmdate('n', $ts);
            $d = (int)gmdate('j', $ts);
            $h = (int)gmdate('G', $ts);

            $where = ['year' => $y, 'month' => $m, 'day' => $d, 'hour' => $h];
            $row = $DB->get_record('local_kopere_status_hourly', $where, 'uptime');
            $last24[] = $row ? (int)$row->uptime : 0;
        }
        $uptime24 = null;
        if (!empty($last24)) {
            $uptime24 = round(array_sum($last24) / count($last24), 2);
        }

        // 3) Build hour bars for the desired window (default 120 hours)
        $windowhours = max(1, $windowhours);
        $hours = [];
        for ($i = $windowhours; $i >= 1; $i--) {
            $ts = $hnow - ($i * 3600);
            $y = (int)gmdate('Y', $ts);
            $m = (int)gmdate('n', $ts);
            $d = (int)gmdate('j', $ts);
            $h = (int)gmdate('G', $ts);

            $where = ['year' => $y, 'month' => $m, 'day' => $d, 'hour' => $h];
            $row = $DB->get_record('local_kopere_status_hourly', $where, 'uptime');

            $pct = $row ? (int)$row->uptime : 0;

            // Map to color class (tweak thresholds to your taste).
            if ($row === false) {
                // If you prefer to visualize "no data" explicitly, uncomment next line:
                // $cls = 'nodata';
                // But requirement says missing -> treated as DOWN.
                $cls = 'down';
            } else if ($pct >= 99) {
                $cls = 'ok';
            } else if ($pct >= 95) {
                $cls = 'good';
            } else if ($pct >= 80) {
                $cls = 'warn';
            } else if ($pct > 0) {
                $cls = 'bad';
            } else {
                $cls = 'down';
            }

            $hours[] = [
                'cls' => $cls,
                'pct' => $pct,
                'title' => sprintf('%04d-%02d-%02d %02d:00 — %d%%', $y, $m, $d, $h, $pct),
            ];
        }

        return [
            'title' => $title,
            'overall' => $overall,
            'overall_label' => $overalllabel,
            'uptime24h' => $uptime24,
            'hours' => $hours,
        ];
    }
}
