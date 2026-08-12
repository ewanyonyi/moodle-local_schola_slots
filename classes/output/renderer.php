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

namespace local_academic_timetabler\output;

use local_academic_timetabler\licensing\license_manager;
use plugin_renderer_base;

/**
 * Output renderer for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render main plugin dashboard interface.
     *
     * @param mixed $page Page context object or array.
     * @return string Rendered HTML content.
     */
    public function render_dashboard($page) {
        global $DB;

        $isenterprise = license_manager::is_enterprise();
        $indexurl = new \moodle_url('/local/academic_timetabler/index.php');
        $roomsurl = new \moodle_url('/local/academic_timetabler/rooms.php');
        $slotsurl = new \moodle_url('/local/academic_timetabler/slots.php');
        $schedulesurl = new \moodle_url('/local/academic_timetabler/schedules.php');
        $settingsurl = new \moodle_url('/admin/settings.php', ['section' => 'local_academic_timetabler_settings']);
        $tasksurl = new \moodle_url('/admin/settings.php', ['section' => 'scheduledtasks']);

        $coursecount = $DB->count_records('course', ['visible' => 1]);
        $roomcount = $DB->count_records('local_att_rooms');
        $schedulecount = $DB->count_records('local_att_schedules');

        $maxcourses = license_manager::get_max_courses();
        $maxlabel = ($maxcourses === 0) ? 'Unlimited' : $maxcourses;

        $contextdata = [
            'is_enterprise' => $isenterprise,
            'tier_name' => $isenterprise ? 'Enterprise Edition' : 'Community Edition',
            'tier_notice' => $isenterprise
                ? get_string('license_enterprise_active', 'local_academic_timetabler')
                : get_string('license_community_notice', 'local_academic_timetabler'),
            'index_url' => $indexurl->out(false),
            'rooms_url' => $roomsurl->out(false),
            'slots_url' => $slotsurl->out(false),
            'schedules_url' => $schedulesurl->out(false),
            'settings_url' => $settingsurl->out(false),
            'tasks_url' => $tasksurl->out(false),
            'buy_url' => 'https://lemonsqueezy.com',
            'course_count' => $coursecount,
            'max_courses_label' => $maxlabel,
            'room_count' => $roomcount,
            'schedule_count' => $schedulecount,
        ];

        return $this->render_from_template('local_academic_timetabler/dashboard', $contextdata);
    }
}
