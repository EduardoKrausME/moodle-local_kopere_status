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
use Exception;
use local_kopere_status\local\checker;

/**
 * Scheduled task: performs checks respecting configured interval.
 */
class ping_task extends scheduled_task {

    /**
     * get_name
     *
     * @return string
     * @throws Exception
     */
    public function get_name() {
        return "StatusBoard checks";
    }

    /**
     * execute
     *
     * @return void
     * @throws Exception
     */
    public function execute() {
        $enabled = get_config('local_kopere_status', 'enabled');
        if (!$enabled) {
            mtrace('local_kopere_status: disabled.');
            return;
        }

        $interval = max(1, get_config('local_kopere_status', 'intervalminutes'));
        $now = time();
        $lastrun = get_config('local_kopere_status', 'lastrun');

        // Gate by interval to allow flexible cron schedule.
        if ($lastrun && ($now - $lastrun) < ($interval * 60)) {
            mtrace('local_kopere_status: skipping (interval gate).');
            return;
        }

        set_config('lastrun', $now, 'local_kopere_status');

        $c = new checker();

        // HTTP (health endpoint).
        $http = $c->check_http();
        $c->log('http', $http['ok'], $http['latencyms'], $http['httpcode'], $http['message']);
    }
}
