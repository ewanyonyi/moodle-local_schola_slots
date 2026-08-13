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
     * Render unified executive navigation header across plugin pages.
     *
     * @param string $activepage Active page key ('index', 'rooms', 'slots', 'schedules').
     * @param bool $showclearall Whether to display the Clear All action button.
     * @param string $scheduletype Schedule type key ('all', 'class', 'exam').
     * @return string Rendered HTML header bar.
     */
    public static function render_nav_header(string $activepage = 'index', bool $showclearall = false, string $scheduletype = 'all'): string {
        $indexurl = new \moodle_url('/local/academic_timetabler/index.php');
        $roomsurl = new \moodle_url('/local/academic_timetabler/rooms.php');
        $slotsurl = new \moodle_url('/local/academic_timetabler/slots.php');
        $templatesurl = new \moodle_url('/local/academic_timetabler/templates.php');
        $schedulesurl = new \moodle_url('/local/academic_timetabler/schedules.php');
        $generateurl = new \moodle_url('/local/academic_timetabler/index.php', [
            'action' => 'generate',
            'sesskey' => sesskey(),
        ]);

        $navitems = [
            'index'     => ['label' => 'Overview', 'url' => $indexurl],
            'rooms'     => ['label' => 'Manage Rooms', 'url' => $roomsurl],
            'slots'     => ['label' => 'Manage Time Slots', 'url' => $slotsurl],
            'templates' => ['label' => 'Schedule Templates', 'url' => $templatesurl],
            'schedules' => ['label' => 'View Timetables', 'url' => $schedulesurl],
        ];

        $html = \html_writer::start_div('bg-white border rounded shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3');

        // Navigation Tabs (Pills)
        $html .= \html_writer::start_div('nav nav-pills gap-2');
        foreach ($navitems as $key => $item) {
            $isactive = ($key === $activepage);
            $cls = $isactive
                ? 'nav-link active bg-primary font-weight-bold px-3 py-2 shadow-sm'
                : 'nav-link text-dark fw-semibold px-3 py-2 bg-light border border-secondary-subtle';
            $html .= \html_writer::link($item['url'], $item['label'], ['class' => $cls]);
        }
        $html .= \html_writer::end_div();

        // Action Buttons Group
        $html .= \html_writer::start_div('d-flex align-items-center gap-2 flex-wrap');
        $html .= \html_writer::link($generateurl, 'Generate Timetable', [
            'class' => 'btn btn-success font-weight-bold px-3 py-2 shadow-sm',
        ]);

        if ($showclearall) {
            $clearurl = new \moodle_url($schedulesurl, ['action' => 'clearall', 'type' => $scheduletype, 'sesskey' => sesskey()]);
            $cleartitle = ($scheduletype !== 'all') ? 'Clear ' . strtoupper($scheduletype) . ' Timetables' : 'Clear All Timetables';
            $html .= \html_writer::link($clearurl, $cleartitle, [
                'class' => 'btn btn-outline-danger font-weight-bold px-3 py-2',
                'onclick' => 'return confirm("Are you sure you want to delete selected generated schedule entries?");',
            ]);
        }
        $html .= \html_writer::end_div();

        $html .= \html_writer::end_div();

        return $html;
    }

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
        $generateurl = new \moodle_url('/local/academic_timetabler/index.php', [
            'action' => 'generate',
            'sesskey' => sesskey(),
        ]);

        $coursecount = $DB->count_records_select('course', 'id > 1 AND visible = 1');
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
            'generate_url' => $generateurl->out(false),
            'buy_url' => 'https://lemonsqueezy.com',
            'course_count' => $coursecount,
            'max_courses_label' => $maxlabel,
            'room_count' => $roomcount,
            'schedule_count' => $schedulecount,
        ];

        return $this->render_from_template('local_academic_timetabler/dashboard', $contextdata);
    }
}
