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
 * View, edit, delete and export generated timetables for local_academic_timetabler.
 * Supports multi-timetable profiles (Class vs Exam schedules) and departmental filtering.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/academic_timetabler:manage', $context);

$view         = optional_param('view', 'grid', PARAM_ALPHA); // 'grid' or 'table'
$action       = optional_param('action', '', PARAM_ALPHA);
$id           = optional_param('id', 0, PARAM_INT);
$roomid       = optional_param('roomid', 0, PARAM_INT);
$teacherid    = optional_param('teacherid', 0, PARAM_INT);
$scheduletype = optional_param('type', 'all', PARAM_ALPHA); // 'all', 'class', 'exam'
$categoryid   = optional_param('categoryid', 0, PARAM_INT);

$url = new moodle_url('/local/academic_timetabler/schedules.php', [
    'view' => $view, 'roomid' => $roomid, 'teacherid' => $teacherid,
    'type' => $scheduletype, 'categoryid' => $categoryid,
]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_schedules', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('manage_schedules', 'local_academic_timetabler'));

// -------------------------------------------------------------------
// Action: Clear All Timetables
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    if ($scheduletype !== 'all') {
        $DB->delete_records('local_academic_timetabler_schedules', ['schedule_type' => $scheduletype]);
        redirect($url, strtoupper($scheduletype) . ' timetables cleared successfully.');
    } else {
        $DB->delete_records('local_academic_timetabler_schedules');
        redirect($url, 'All generated timetables cleared successfully.');
    }
}

// -------------------------------------------------------------------
// Action: Delete Single Schedule Entry
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_academic_timetabler_schedules', ['id' => $id]);
    redirect($url, 'Schedule allocation deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Set Day Distribution Strategy
// -------------------------------------------------------------------
if ($action === 'setstrategy' && confirm_sesskey()) {
    $strat = optional_param('strategy', 'balanced', PARAM_ALPHA);
    set_config('day_distribution', $strat, 'local_academic_timetabler');
    redirect($url, 'Day distribution strategy updated successfully.');
}

// -------------------------------------------------------------------
// Action: Manual Edit Allocation
// -------------------------------------------------------------------
$editschedule = null;
if ($action === 'edit' && $id > 0) {
    $editschedule = $DB->get_record('local_academic_timetabler_schedules', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('submitedit', 0, PARAM_INT)) {
    $editid = optional_param('schedid', 0, PARAM_INT);
    $newroomid = optional_param('edit_roomid', 0, PARAM_INT);
    $newslotid = optional_param('edit_slotid', 0, PARAM_INT);
    $newteacherid = optional_param('edit_teacherid', 0, PARAM_INT);

    if ($editid > 0 && $newroomid > 0 && $newslotid > 0) {
        // Conflict Check: Room conflict
        $roomconflict = $DB->get_record_sql(
            "SELECT id FROM {local_academic_timetabler_schedules} WHERE id != :id AND roomid = :roomid AND slotid = :slotid",
            ['id' => $editid, 'roomid' => $newroomid, 'slotid' => $newslotid]
        );

        // Conflict Check: Teacher conflict
        $teacherconflict = false;
        if ($newteacherid > 0) {
            $teacherconflict = $DB->get_record_sql(
                "SELECT id FROM {local_academic_timetabler_schedules} WHERE id != :id AND teacherid = :teacherid AND slotid = :slotid",
                ['id' => $editid, 'teacherid' => $newteacherid, 'slotid' => $newslotid]
            );
        }

        if ($roomconflict) {
            \core\notification::warning('Room conflict: Venue is already booked for another course during this time slot.');
        } else if ($teacherconflict) {
            \core\notification::warning('Instructor conflict: Lecturer is already teaching another course during this time slot.');
        } else {
            $rec = (object)[
                'id' => $editid,
                'roomid' => $newroomid,
                'slotid' => $newslotid,
                'teacherid' => $newteacherid,
            ];
            $DB->update_record('local_academic_timetabler_schedules', $rec);
            redirect($url, 'Schedule allocation updated successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_academic_timetabler\output\renderer::render_nav_header('schedules', true, $scheduletype);

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];

// -------------------------------------------------------------------
// Edit Form Card (if Editing)
// -------------------------------------------------------------------
if ($editschedule) {
    $editcourse = $DB->get_record('course', ['id' => $editschedule->courseid]);
    $cardtitle = 'Edit Schedule Allocation: ' . s($editcourse->fullname ?? ('Course ' . $editschedule->courseid));

    echo html_writer::start_div('card border border-primary-subtle shadow-sm mb-4');
    echo html_writer::div(html_writer::tag('h5', $cardtitle, ['class' => 'mb-0 text-dark font-weight-bold']), 'card-header bg-light');
    echo html_writer::start_div('card-body');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submitedit', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'schedid', 'value' => $editschedule->id]);

    // Room options
    $allrooms = $DB->get_records('local_academic_timetabler_rooms', null, 'name ASC');
    $roomopts = [];
    foreach ($allrooms as $r) {
        $roomopts[$r->id] = $r->name . ' (Cap: ' . $r->capacity . ')';
    }

    // Slot options
    $allslots = $DB->get_records('local_academic_timetabler_slots', null, 'dayofweek ASC, starttime ASC');
    $slotopts = [];
    foreach ($allslots as $sl) {
        $dname = $days[$sl->dayofweek] ?? 'Day ' . $sl->dayofweek;
        $slotopts[$sl->id] = $dname . ' (' . $sl->starttime . ' - ' . $sl->endtime . ') [' . strtoupper($sl->type) . ']';
    }

    // Teacher options: Enrolled course teachers + all active institutional faculty members
    $coursecontext = context_course::instance($editschedule->courseid);
    $courseteachers = get_enrolled_users($coursecontext, 'moodle/course:update', 0, 'u.id, u.firstname, u.lastname');

    $allteachers = $DB->get_records_sql("
        SELECT DISTINCT u.id, u.firstname, u.lastname
          FROM {user} u
          JOIN {user_enrolments} ue ON ue.userid = u.id
         WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1
      ORDER BY u.firstname ASC, u.lastname ASC
    ");

    $teacheropts = [0 => '-- Unassigned --'];
    foreach ($allteachers as $t) {
        $prefix = isset($courseteachers[$t->id]) ? '[Course Teacher] ' : '';
        $teacheropts[$t->id] = $prefix . fullname($t);
    }

    echo html_writer::start_div('row g-3');
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('label', 'Assigned Room / Venue', ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($roomopts, 'edit_roomid', $editschedule->roomid, false, ['class' => 'form-select']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('label', 'Assigned Day & Time Slot', ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($slotopts, 'edit_slotid', $editschedule->slotid, false, ['class' => 'form-select']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('label', 'Assigned Instructor', ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($teacheropts, 'edit_teacherid', $editschedule->teacherid, false, ['class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('mt-3 d-flex gap-2');
    echo html_writer::tag('button', 'Save Schedule Allocation', ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-outline-secondary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Fetch options for filter dropdowns
$allrooms = $DB->get_records('local_academic_timetabler_rooms', null, 'name ASC');
$roomoptions = [0 => '-- All Campus Venues --'];
foreach ($allrooms as $r) {
    $roomoptions[$r->id] = $r->name . ' (' . $r->capacity . ' seats)';
}

$allteachers = $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname
                                      FROM {user} u
                                      JOIN {local_academic_timetabler_schedules} s ON s.teacherid = u.id
                                  ORDER BY u.lastname ASC");
$teacheroptions = [0 => '-- All Faculty Members --'];
foreach ($allteachers as $t) {
    $teacheroptions[$t->id] = fullname($t);
}

// Categories / Departments filter options
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$catoptions = [0 => '-- All Departments --'] + $categories;

// Type filter options
$typefilteroptions = [
    'all'   => 'Profile: All Timetables',
    'class' => 'Profile: Class Timetables Only',
    'exam'  => 'Profile: Exam Timetables Only',
];

// Strategy options
$currentstrategy = get_config('local_academic_timetabler', 'day_distribution') ?: 'balanced';
$strategyoptions = [
    'balanced'   => 'Strategy: Balanced 5-Day (Mon-Fri)',
    'mon_to_sat' => 'Strategy: 6-Day Schedule (Mon-Sat)',
    'mon_to_thu' => 'Strategy: 4-Day Compact (Mon-Thu)',
    'frontload'   => 'Strategy: Sequential Frontload',
];

// Summary counts
$classcount = $DB->count_records('local_academic_timetabler_schedules', ['schedule_type' => 'class']);
$examcount  = $DB->count_records('local_academic_timetabler_schedules', ['schedule_type' => 'exam']);
$totalcount = $classcount + $examcount;

// -------------------------------------------------------------------
// Summary Count Badges
// -------------------------------------------------------------------
echo html_writer::start_div('d-flex gap-3 align-items-center mb-3 flex-wrap');
echo html_writer::div("Total Allocations: <strong>{$totalcount}</strong>", 'badge bg-dark text-white p-2 fs-6');
echo html_writer::div("Class Schedules: <strong>{$classcount}</strong>", 'badge bg-primary text-white p-2 fs-6');
echo html_writer::div("Exam Schedules: <strong>{$examcount}</strong>", 'badge bg-warning text-dark p-2 fs-6');
echo html_writer::end_div();

// -------------------------------------------------------------------
// Export & View Control Toolbar
// -------------------------------------------------------------------
$csvexporturl = new moodle_url('/local/academic_timetabler/export.php', [
    'action' => 'csv', 'roomid' => $roomid, 'teacherid' => $teacherid, 'type' => $scheduletype, 'categoryid' => $categoryid,
]);
$pdfexporturl = new moodle_url('/local/academic_timetabler/export.php', [
    'action' => 'print', 'roomid' => $roomid, 'teacherid' => $teacherid, 'type' => $scheduletype, 'categoryid' => $categoryid, 'autoprint' => 1,
]);

echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::start_div('card-body d-flex flex-wrap align-items-center justify-content-between gap-3');

// Left side: Export / Print Action Buttons
echo html_writer::start_div('d-flex gap-2 flex-wrap');
echo html_writer::link($csvexporturl, 'Export to CSV', ['class' => 'btn btn-outline-success font-weight-bold']);
echo html_writer::link($pdfexporturl, 'Print / Save to PDF', ['class' => 'btn btn-outline-primary font-weight-bold', 'target' => '_blank']);
echo html_writer::end_div();

// Strategy selector form
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'd-flex gap-1 align-items-center']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'setstrategy']);
echo html_writer::select($strategyoptions, 'strategy', $currentstrategy, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
echo html_writer::end_tag('form');

// Middle: View Mode Toggle
echo html_writer::start_div('btn-group', ['role' => 'group']);
$gridactive = ($view === 'grid') ? 'btn-primary' : 'btn-outline-primary';
$tableactive = ($view === 'table') ? 'btn-primary' : 'btn-outline-primary';
echo html_writer::link(new moodle_url($url, ['view' => 'grid']), 'Weekly Matrix', ['class' => "btn btn-sm {$gridactive}"]);
echo html_writer::link(new moodle_url($url, ['view' => 'table']), 'Master List', ['class' => "btn btn-sm {$tableactive}"]);
echo html_writer::end_div();

// Right side: Filter Controls (Type, Department, Room, Teacher)
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false), 'class' => 'd-flex gap-2 align-items-center flex-wrap']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);
echo html_writer::select($typefilteroptions, 'type', $scheduletype, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
echo html_writer::select($catoptions, 'categoryid', $categoryid, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
echo html_writer::select($roomoptions, 'roomid', $roomid, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
echo html_writer::select($teacheroptions, 'teacherid', $teacherid, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);

if ($roomid > 0 || $teacherid > 0 || $categoryid > 0 || $scheduletype !== 'all') {
    echo html_writer::link(new moodle_url($url, ['roomid' => 0, 'teacherid' => 0, 'categoryid' => 0, 'type' => 'all']), 'Reset Filters', ['class' => 'btn btn-sm btn-outline-secondary']);
}
echo html_writer::end_tag('form');

echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Query Schedules
// -------------------------------------------------------------------
$where = [];
$params = [];

if ($scheduletype !== 'all') {
    $where[] = 's.schedule_type = :stype';
    $params['stype'] = $scheduletype;
}
if ($categoryid > 0) {
    $where[] = 'c.category = :categoryid';
    $params['categoryid'] = $categoryid;
}
if ($roomid > 0) {
    $where[] = 's.roomid = :roomid';
    $params['roomid'] = $roomid;
}
if ($teacherid > 0) {
    $where[] = 's.teacherid = :teacherid';
    $params['teacherid'] = $teacherid;
}
$wherestr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT s.id, s.schedule_type, c.shortname AS coursecode, c.fullname AS coursename,
               r.name AS roomname, sl.dayofweek, sl.starttime, sl.endtime,
               u.firstname, u.lastname
          FROM {local_academic_timetabler_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_academic_timetabler_rooms} r ON r.id = s.roomid
          JOIN {local_academic_timetabler_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
          {$wherestr}
      ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

$schedules = $DB->get_records_sql($sql, $params);

if (empty($schedules)) {
    echo html_writer::div(get_string('no_schedules', 'local_academic_timetabler'), 'alert alert-info');
} else if ($view === 'grid') {
    // -------------------------------------------------------------------
    // 5-Day / 6-Day Weekly Matrix View
    // -------------------------------------------------------------------
    $maxday = ($currentstrategy === 'mon_to_sat') ? 6 : 5;
    $matrixdays = array_slice($days, 0, $maxday, true);

    echo html_writer::tag('h4', 'Weekly Timetable Grid Matrix (' . count($matrixdays) . ' Days)', ['class' => 'mb-3 font-weight-bold']);

    // Fetch all configured slots to include Break windows in the matrix
    $allslots = $DB->get_records('local_academic_timetabler_slots', null, 'starttime ASC');
    $timeblocks = [];
    $breakwindows = [];
    foreach ($allslots as $sl) {
        $window = s($sl->starttime) . ' - ' . s($sl->endtime);
        if (!in_array($window, $timeblocks)) {
            $timeblocks[] = $window;
        }
        if ($sl->type === 'break') {
            $breakwindows[$window] = true;
        }
    }
    foreach ($schedules as $s) {
        $window = s($s->starttime) . ' - ' . s($s->endtime);
        if (!in_array($window, $timeblocks)) {
            $timeblocks[] = $window;
        }
    }
    sort($timeblocks);

    // Group schedules by timeblock and dayofweek
    $matrix = [];
    foreach ($schedules as $s) {
        $window = s($s->starttime) . ' - ' . s($s->endtime);
        $matrix[$window][$s->dayofweek][] = $s;
    }

    echo html_writer::start_div('table-responsive shadow-sm rounded border mb-4');
    echo html_writer::start_tag('table', ['class' => 'table table-bordered align-middle text-center mb-0', 'style' => 'font-size:13px;']);
    echo html_writer::start_tag('thead', ['class' => 'table-dark']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Time Window', ['style' => 'width: 14%; font-size:14px;']);
    foreach ($matrixdays as $daynum => $dayname) {
        echo html_writer::tag('th', $dayname, ['style' => 'font-size:14px;']);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($timeblocks as $timeblock) {
        $isbreak = isset($breakwindows[$timeblock]);
        echo html_writer::start_tag('tr');

        if ($isbreak) {
            echo html_writer::tag('td', '<strong>' . $timeblock . '</strong><br><span class="badge att-badge-break text-white mt-1">BREAK WINDOW</span>', ['class' => 'bg-light font-weight-bold align-middle']);
            echo html_writer::tag('td', '<span class="text-muted fw-semibold">INSTITUTIONAL BREAK / BLOCKOUT &mdash; NO CLASSES OR EXAMS</span>', [
                'colspan' => count($matrixdays),
                'class'   => 'bg-light text-secondary text-center py-2 align-middle',
            ]);
        } else {
            echo html_writer::tag('td', '<strong>' . $timeblock . '</strong>', ['class' => 'bg-light font-weight-bold']);

            foreach (array_keys($matrixdays) as $day) {
                echo html_writer::start_tag('td', ['class' => 'p-2']);
                $entries = $matrix[$timeblock][$day] ?? [];
                if (empty($entries)) {
                    echo '<span class="text-muted small">&mdash;</span>';
                } else {
                    foreach ($entries as $entry) {
                        $teacher = (!empty($entry->firstname) || !empty($entry->lastname)) ? fullname($entry) : 'Unassigned';
                        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $entry->id]);
                        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $entry->id, 'sesskey' => sesskey()]);
                        $typebadge = ($entry->schedule_type === 'exam') ? '<span class="badge att-badge-exam me-1">EXAM</span>' : '';

                        echo html_writer::start_div('bg-white border rounded shadow-sm p-2 mb-2 text-start', ['style' => 'border-color: #cbd5e1 !important;']);
                        echo html_writer::div($typebadge . s($entry->coursecode), '', ['style' => 'color: #0f172a; font-weight: 700; font-size: 13px;']);
                        echo html_writer::div(s($entry->roomname), 'mt-1', ['style' => 'color: #334155; font-weight: 600; font-size: 12px;']);
                        echo html_writer::div(s($teacher), '', ['style' => 'color: #475569; font-size: 12px;']);

                        // Clean action links
                        echo html_writer::start_div('mt-2 pt-1 border-top d-flex gap-1');
                        echo html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary py-0 px-2 fs-7']);
                        echo html_writer::link($delurl, 'Delete', [
                            'class' => 'btn btn-sm btn-outline-danger py-0 px-2 fs-7',
                            'onclick' => 'return confirm("Delete this allocation?");',
                        ]);
                        echo html_writer::end_div();

                        echo html_writer::end_div();
                    }
                }
                echo html_writer::end_tag('td');
            }
        }
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

} else {
    // -------------------------------------------------------------------
    // Master List Table View
    // -------------------------------------------------------------------
    echo html_writer::tag('h4', 'Master Schedule Allocations List', ['class' => 'mb-3 font-weight-bold']);
    $table = new html_table();
    $table->head = ['Schedule ID', 'Course Code & Name', 'Assigned Room', 'Day & Time Window', 'Instructor', 'Type', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($schedules as $sched) {
        $dayname = $days[$sched->dayofweek] ?? 'Day ' . $sched->dayofweek;
        $teacher = (!empty($sched->firstname) || !empty($sched->lastname)) ? fullname($sched) : 'Unassigned';

        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $sched->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-1']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $sched->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("Are you sure you want to delete this schedule allocation?");',
        ]);

        $typecls = ($sched->schedule_type === 'exam') ? 'att-badge-exam' : 'att-badge-class';

        $table->data[] = [
            $sched->id,
            '<strong>' . s($sched->coursecode) . '</strong> - ' . s($sched->coursename),
            s($sched->roomname),
            '<strong>' . $dayname . '</strong> (' . s($sched->starttime) . ' - ' . s($sched->endtime) . ')',
            s($teacher),
            '<span class="badge ' . $typecls . '">' . strtoupper($sched->schedule_type) . '</span>',
            $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
