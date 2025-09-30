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
 * Core checker for HTTP self health and DB test.
 */
class checker {

    /**
     * Hit the local health endpoint to measure HTTP stack latency.
     *
     * @return array{ok:bool, latencyms:int, httpcode:int, message:string}
     * @throws Exception
     */
    public function check_http() {
        $t0 = microtime(true);

        $url = (new \moodle_url('/local/kopere_status/health.php'));
        $token = get_config('local_kopere_status', 'healthtoken');
        if (!empty($token)) {
            $url->param('token', $token);
        }

        $curl = new \curl();
        $curl->setopt(['CURLOPT_CONNECTTIMEOUT' => 5, 'CURLOPT_TIMEOUT' => 10]);
        $resp = $curl->get($url->out(false));
        $code = (int) $curl->get_info()['http_code'] ?? 0;

        $ok = ($code === 200) && strpos((string) $resp, '"ok":true') !== false;
        $lat = (int) round((microtime(true) - $t0) * 1000);
        $msg = $ok ? 'HTTP OK' : 'HTTP error: ' . $code;

        return ['ok' => $ok, 'latencyms' => $lat, 'httpcode' => $code, 'message' => $msg];
    }

    /**
     * Store a log row.
     *
     * @param bool $ok
     * @param int $latency
     * @param int $httpcode
     * @param string $message
     * @return void
     * @throws Exception
     */
    public function log(bool $ok, int $latency, int $httpcode, string $message) {
        global $DB;
        $row = (object) [
            'timecreated' => time(), 'status' => $ok ? 1 : 0, 'latencyms' => $latency, 'httpcode' => $httpcode,
            'message' => $message,
        ];
        $DB->insert_record('local_kopere_status_log', $row);
    }

    /**
     * Return the latest samples for 'http' and 'db'.
     *
     * Rules:
     * - Uses the most recent row in local_statusboard_log for each type.
     * - "Freshness" threshold = 2 * intervalminutes.
     * - If no recent sample exists (e.g., logs purged or service OFF), we synthesize a
     *   "stale" sample with status=0 (DOWN) so the UI reflects downtime in absence of data.
     *
     * @return array
     */
    public function last_samples() {
        global $DB;

        $intervalmin = max(1, (int) get_config('local_statusboard', 'intervalminutes'));
        $freshcut = time() - (2 * $intervalmin * 60); // Anything older than this is considered stale.

        $out = [];

        foreach (['http', 'db'] as $type) {
            // Get the most recent log for this type.
            $recs = $DB->get_records_sql(
                "SELECT id, timecreated, type, status, latencyms, httpcode, message
                   FROM {local_statusboard_log}
                  WHERE type = ?
               ORDER BY timecreated DESC, id DESC", [$type], 0, 1
            );
            $rec = $recs ? reset($recs) : null;

            if ($rec) {
                $fresh = ((int) $rec->timecreated >= $freshcut) ? 1 : 0;
                $out[$type] = [
                    'id' => (int) $rec->id, 'timecreated' => (int) $rec->timecreated, 'type' => (string) $rec->type,
                    'status' => (int) $rec->status, 'latencyms' => (int) $rec->latencyms, 'httpcode' => (int) $rec->httpcode,
                    'message' => (string) $rec->message, 'fresh' => $fresh, 'stale' => $fresh ? 0 : 1,
                ];
                continue;
            }

            // No raw sample available (e.g., logs already rolled-up or system was OFF).
            // Synthesize a stale/down sample to reflect unavailability in the UI.
            $out[$type] = [
                'id' => 0, 'timecreated' => (int) floor(time() / 3600) * 3600, // Current hour boundary (approx).
                'type' => $type, 'status' => 0,           // DOWN if we have no evidence of UP.
                'latencyms' => 0, 'httpcode' => 0, 'message' => 'no recent samples', 'fresh' => 0, 'stale' => 1,
            ];
        }

        return $out;
    }

    /**
     * Build the current summary status using last samples and freshness.
     *
     * Overall logic:
     * - If both 'http' and 'db' are UP and fresh => 'operational'
     * - If exactly one is UP and fresh => 'partial'
     * - Otherwise => 'down'
     *
     * @return array
     */
    public function summary() {
        $samples = $this->last_samples();

        // Treat stale or missing data as DOWN to surface outages when no logs exist.
        $httpok =
            (isset($samples['http']) && (int) $samples['http']['fresh'] === 1 && (int) $samples['http']['status'] === 1) ? 1 : 0;
        $dbok = (isset($samples['db']) && (int) $samples['db']['fresh'] === 1 && (int) $samples['db']['status'] === 1) ? 1 : 0;

        $sum = $httpok + $dbok;
        $overall = ($sum === 2) ? 'operational' : (($sum === 1) ? 'partial' : 'down');

        return [
            'overall' => $overall, 'components' => [
                'http' => $httpok, 'db' => $dbok,
            ], 'samples' => $samples,
        ];
    }

}
