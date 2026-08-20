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
 * View, edit, delete and export generated timetables for local_schola_slots.
 * Supports multi-timetable profiles (Class vs Exam schedules) and departmental filtering.
 *
 * @package     local_schola_slots
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/schola_slots:manage', $context);

$action       = optional_param('action', '', PARAM_ALPHA);
$id           = optional_param('id', 0, PARAM_INT);
$roomid       = optional_param('roomid', 0, PARAM_INT);
$teacherid    = optional_param('teacherid', 0, PARAM_INT);
$scheduletype = optional_param('type', 'all', PARAM_ALPHA); // 'all', 'class', 'exam'
$categoryid   = optional_param('categoryid', 0, PARAM_INT);
$editmode     = optional_param('editmode', 0, PARAM_INT);
$openbreaks   = optional_param('openbreaks', 0, PARAM_INT);

$url = new moodle_url('/local/schola_slots/schedules.php', [
    'roomid' => $roomid, 'teacherid' => $teacherid,
    'type' => $scheduletype, 'categoryid' => $categoryid,
    'editmode' => $editmode,
]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_schedules', 'local_schola_slots'));
$PAGE->set_heading(get_string('manage_schedules', 'local_schola_slots'));

// Render unified executive navigation header
echo local_schola_slots\output\renderer::render_nav_header('schedules', true, $scheduletype);

// -------------------------------------------------------------------
// Action: Clear All Timetables
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    if ($scheduletype !== 'all') {
        $DB->delete_records('local_schola_slots_schedules', ['schedule_type' => $scheduletype]);
        redirect($url, strtoupper($scheduletype) . ' timetables cleared successfully.');
    } else {
        $DB->delete_records('local_schola_slots_schedules');
        redirect($url, 'All generated timetables cleared successfully.');
    }
}

// -------------------------------------------------------------------
// Action: Delete Single Schedule Entry
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_slots_schedules', ['id' => $id]);
    redirect($url, 'Schedule allocation deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Set Day Distribution Strategy
// -------------------------------------------------------------------
if ($action === 'setstrategy' && confirm_sesskey()) {
    $strat = optional_param('strategy', 'balanced', PARAM_ALPHA);
    set_config('day_distribution', $strat, 'local_schola_slots');
    redirect($url, 'Day distribution strategy updated successfully.');
}

// -------------------------------------------------------------------
// Action: Manual Edit Allocation
// -------------------------------------------------------------------
$editschedule = null;
if ($action === 'edit' && $id > 0) {
    $editschedule = $DB->get_record('local_schola_slots_schedules', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('submitedit', 0, PARAM_INT)) {
    $editid = optional_param('schedid', 0, PARAM_INT);
    $newroomid = optional_param('edit_roomid', 0, PARAM_INT);
    $newslotid = optional_param('edit_slotid', 0, PARAM_INT);
    $newteacherid = optional_param('edit_teacherid', 0, PARAM_INT);

    if ($editid > 0 && $newroomid > 0 && $newslotid > 0) {
        // Conflict Check: Room conflict
        $roomconflict = $DB->get_record_sql(
            "SELECT id FROM {local_schola_slots_schedules} WHERE id != :id AND roomid = :roomid AND slotid = :slotid",
            ['id' => $editid, 'roomid' => $newroomid, 'slotid' => $newslotid]
        );

        // Conflict Check: Teacher conflict
        $teacherconflict = false;
        if ($newteacherid > 0) {
            $teacherconflict = $DB->get_record_sql(
                "SELECT id FROM {local_schola_slots_schedules} WHERE id != :id AND teacherid = :teacherid AND slotid = :slotid",
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
            $DB->update_record('local_schola_slots_schedules', $rec);
            redirect($url, 'Schedule allocation updated successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_schola_slots\output\renderer::render_nav_header('schedules', true, $scheduletype);

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
    $allrooms = $DB->get_records('local_schola_slots_rooms', null, 'name ASC');
    $roomopts = [];
    foreach ($allrooms as $r) {
        $roomopts[$r->id] = $r->name . ' (Cap: ' . $r->capacity . ')';
    }

    // Slot options
    $allslots = $DB->get_records('local_schola_slots_slots', null, 'dayofweek ASC, starttime ASC');
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
$allrooms = $DB->get_records('local_schola_slots_rooms', null, 'name ASC');
$roomoptions = [0 => get_string('all_campus_venues', 'local_schola_slots')];
foreach ($allrooms as $r) {
    $roomoptions[$r->id] = $r->name . ' (' . $r->capacity . ' seats)';
}

$allteachers = $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname
                                      FROM {user} u
                                      JOIN {local_schola_slots_schedules} s ON s.teacherid = u.id
                                  ORDER BY u.lastname ASC");
$teacheroptions = [0 => get_string('all_faculty_members', 'local_schola_slots')];
foreach ($allteachers as $t) {
    $teacheroptions[$t->id] = fullname($t);
}

// Categories / Departments filter options
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$catoptions = [0 => get_string('all_departments_filter', 'local_schola_slots')] + $categories;

// Type filter options
$typefilteroptions = [
    'all'   => get_string('profile_all_timetables', 'local_schola_slots'),
    'class' => get_string('profile_class_only', 'local_schola_slots'),
    'exam'  => get_string('profile_exam_only', 'local_schola_slots'),
];

// Strategy options
$currentstrategy = get_config('local_schola_slots', 'day_distribution') ?: 'balanced';
$strategyoptions = [
    'balanced'   => get_string('strategy_balanced', 'local_schola_slots'),
    'mon_to_sat' => get_string('strategy_6day', 'local_schola_slots'),
    'mon_to_thu' => get_string('strategy_4day', 'local_schola_slots'),
    'frontload'  => get_string('strategy_frontload', 'local_schola_slots'),
];

// Summary counts
$classcount = $DB->count_records('local_schola_slots_schedules', ['schedule_type' => 'class']);
$examcount  = $DB->count_records('local_schola_slots_schedules', ['schedule_type' => 'exam']);
$totalcount = $classcount + $examcount;

$csvexporturl = new moodle_url('/local/schola_slots/export.php', [
    'action' => 'csv', 'roomid' => $roomid, 'teacherid' => $teacherid, 'type' => $scheduletype, 'categoryid' => $categoryid,
]);
$pdfexporturl = new moodle_url('/local/schola_slots/export.php', [
    'action' => 'print', 'roomid' => $roomid, 'teacherid' => $teacherid, 'type' => $scheduletype, 'categoryid' => $categoryid, 'autoprint' => 1,
]);

// -------------------------------------------------------------------
// Executive Timetable Header Banner (Mirrors Rust scholaslots.com design)
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white');
echo html_writer::start_div('d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3');

echo html_writer::start_div();
echo html_writer::start_div('d-flex align-items-center gap-2 mb-2');
echo html_writer::tag('span', 'Fitness Score: 100', ['class' => 'badge-fitness-score']);
echo html_writer::tag('span', 'ID #2', ['class' => 'badge-id-num']);
echo html_writer::end_div();

echo html_writer::start_div('d-flex align-items-center gap-3');
echo html_writer::tag('h2', 'Master Class Timetable 2026', ['class' => 'fw-bold text-dark mb-0']);
$typelabel = ($scheduletype === 'all') ? 'CLASS SCHEDULE' : strtoupper($scheduletype) . ' SCHEDULE';
echo html_writer::tag('span', $typelabel, ['class' => 'badge-class-schedule']);
echo html_writer::end_div();
$subtext = 'Generated by Schola Slots Cloud Solver on 2026-08-19 13:27 using License Key <span class="badge-license-key">SYS-ADMIN-1</span>';
echo html_writer::tag('p', $subtext, ['class' => 'text-muted small mb-0 mt-1']);
echo html_writer::end_div();

// Action buttons (Exact Schola Slots Rust brand colors & buttons)
echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
echo html_writer::link($pdfexporturl, '<i class="fa fa-print me-1"></i> Save to PDF', ['class' => 'btn btn-emerald d-inline-flex align-items-center', 'target' => '_blank']);
echo html_writer::link($csvexporturl, '<i class="fa fa-download me-1"></i> Export CSV', ['class' => 'btn btn-outline-slate d-inline-flex align-items-center']);

$toggleediturl = new moodle_url($url, ['editmode' => $editmode ? 0 : 1]);
$editbtnlabel = $editmode ? '<i class="fa fa-check me-1"></i> Disable Edit Mode' : '<i class="fa fa-pencil me-1"></i> Enable Edit Mode';
$editbtnclass = $editmode ? 'btn btn-emerald d-inline-flex align-items-center' : 'btn btn-outline-emerald d-inline-flex align-items-center';

echo html_writer::link($toggleediturl, $editbtnlabel, [
    'class' => $editbtnclass,
    'id' => 'enableEditModeBtn',
    'onclick' => 'toggleEditMode(event);',
]);

$clearallurl = new moodle_url($url, ['action' => 'clearall', 'type' => $scheduletype, 'sesskey' => sesskey()]);
echo html_writer::link($clearallurl, '<i class="fa fa-trash me-1"></i> Delete', [
    'class' => 'btn btn-outline-red d-inline-flex align-items-center',
    'onclick' => 'return confirm("Are you sure you want to clear generated timetables?");',
]);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Unified Filter Toolbar Bar
// -------------------------------------------------------------------
echo html_writer::start_div('card border shadow-sm rounded-4 p-3 mb-4 bg-white');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false), 'class' => 'd-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3']);

echo html_writer::start_div('d-flex flex-wrap align-items-center gap-2');
echo html_writer::tag('span', 'Filters:', ['class' => 'text-dark font-weight-bold small me-1']);

// Strategy Selector
echo html_writer::select($strategyoptions, 'strategy', $currentstrategy, false, [
    'class' => 'form-select form-select-sm search-filter-input',
    'style' => 'max-width: 190px;',
    'onchange' => 'this.form.action="schedules.php?action=setstrategy&sesskey=' . sesskey() . '"; this.form.submit();'
]);

echo html_writer::select($typefilteroptions, 'type', $scheduletype, false, ['class' => 'form-select form-select-sm search-filter-input', 'style' => 'max-width: 170px;', 'onchange' => 'this.form.submit()']);
echo html_writer::select($catoptions, 'categoryid', $categoryid, false, ['class' => 'form-select form-select-sm search-filter-input', 'style' => 'max-width: 190px;', 'onchange' => 'this.form.submit()']);
echo html_writer::select($roomoptions, 'roomid', $roomid, false, ['class' => 'form-select form-select-sm search-filter-input', 'style' => 'max-width: 190px;', 'onchange' => 'this.form.submit()']);
echo html_writer::select($teacheroptions, 'teacherid', $teacherid, false, ['class' => 'form-select form-select-sm search-filter-input', 'style' => 'max-width: 190px;', 'onchange' => 'this.form.submit()']);
echo html_writer::end_div();

echo html_writer::start_div('d-flex align-items-center gap-2');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'scholaLiveSearch',
    'class' => 'form-control form-control-sm search-filter-input',
    'placeholder' => 'Search course/code...',
    'onkeyup' => 'filterTimetableEntries()',
    'style' => 'max-width: 200px;'
]);

if ($roomid > 0 || $teacherid > 0 || $categoryid > 0 || $scheduletype !== 'all') {
    $reseturl = new moodle_url($url, ['roomid' => 0, 'teacherid' => 0, 'categoryid' => 0, 'type' => 'all']);
    echo html_writer::link($reseturl, 'Reset', ['class' => 'btn btn-sm btn-outline-secondary font-weight-bold px-3 py-1.5 rounded-3']);
}
echo html_writer::end_div();

echo html_writer::end_tag('form');
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
               r.name AS roomname, r.id AS room_id, sl.dayofweek, sl.starttime, sl.endtime,
               u.firstname, u.lastname
          FROM {local_schola_slots_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_schola_slots_rooms} r ON r.id = s.roomid
          JOIN {local_schola_slots_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
          {$wherestr}
      ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

$schedules = $DB->get_records_sql($sql, $params);

if (empty($schedules)) {
    echo html_writer::div(get_string('no_schedules', 'local_schola_slots'), 'alert alert-info rounded-3 p-4');
} else {
    // -------------------------------------------------------------------
    // Schola Slots Rust Mirrored Institutional Grid Matrix View
    // -------------------------------------------------------------------
    $roomwhere = ($roomid > 0) ? ['id' => $roomid] : null;
    $allrooms = $DB->get_records('local_schola_slots_rooms', $roomwhere, 'name ASC');
    $roomslist = array_values($allrooms);
    $numrooms = count($roomslist);

    $allslots = $DB->get_records('local_schola_slots_slots', null, 'starttime ASC');
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

    // Group schedules by day, roomid, timeblock
    $matrix = [];
    foreach ($schedules as $s) {
        $window = s($s->starttime) . ' - ' . s($s->endtime);
        $matrix[$s->dayofweek][$s->room_id][$window][] = $s;
    }

    $maxday = ($currentstrategy === 'mon_to_sat') ? 6 : 5;
    $matrixdays = array_slice($days, 0, $maxday, true);

    $gridcontainercls = 'card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white' . ($editmode ? ' edit-mode-active' : '');
    echo html_writer::start_div($gridcontainercls, ['id' => 'institutionalGridContainer']);
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
    echo html_writer::start_div();
    echo html_writer::tag('h5', 'Master Academic Timetable Matrix', ['class' => 'fw-bold text-dark mb-1']);
    echo html_writer::tag('p', 'Official Institutional View — Sticky Venue Headers, Shaded Vertical Break Columns & Online Classes.', ['class' => 'text-muted small mb-0']);
    echo html_writer::end_div();
    echo html_writer::tag('span', 'online classes highlighted', ['class' => 'badge bg-success-subtle text-success border border-success-subtle px-3 py-1 font-monospace small rounded-pill']);
    echo html_writer::end_div();

    echo html_writer::start_div('table-responsive shadow-sm rounded-3 border', ['style' => 'max-height: 75vh; overflow-y: auto;']);
    echo html_writer::start_tag('table', ['class' => 'table table-bordered align-middle mb-0 schola-slots-matrix-table', 'id' => 'institutionalGridTable', 'style' => 'min-width: 1100px;']);
    
    // Header Row
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr', ['class' => 'text-center font-monospace text-uppercase small bg-light']);
    echo html_writer::tag('th', 'VENUE / ROOM', ['style' => 'width: 160px; min-width: 160px;', 'class' => 'py-3 px-3 text-start sticky-top-th']);
    foreach ($timeblocks as $tb) {
        if (isset($breakwindows[$tb])) {
            echo html_writer::tag('th', '<div class="fw-bold">' . $tb . '</div>', ['class' => 'py-2.5 px-2 text-center break-column-strip sticky-top-th', 'style' => 'font-size: 11px; min-width: 110px;']);
        } else {
            echo html_writer::tag('th', '<strong>' . $tb . '</strong>', ['class' => 'py-3 px-2 text-center sticky-top-th', 'style' => 'font-size: 11px; min-width: 110px;']);
        }
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    // Body
    echo html_writer::start_tag('tbody');
    foreach ($matrixdays as $daynum => $dayname) {
        // Day Banner Row
        echo html_writer::start_tag('tr', ['class' => 'day-banner-row']);
        echo html_writer::tag('td', '🗓️ ' . strtoupper($dayname) . ' SCHEDULE', [
            'colspan' => (count($timeblocks) + 1),
            'class' => 'day-banner-cell font-monospace text-uppercase fw-bold py-2 px-3'
        ]);
        echo html_writer::end_tag('tr');

        foreach ($roomslist as $ridx => $room) {
            $isonlineroom = (stripos($room->name, 'online') !== false || stripos($room->name, 'virtual') !== false || stripos($room->name, 'zoom') !== false);
            echo html_writer::start_tag('tr', ['class' => $isonlineroom ? 'table-success-subtle' : '']);

            // Venue Header Cell
            echo html_writer::start_tag('td', ['class' => 'font-monospace small px-3 py-2 fw-bold text-dark venue-sticky-td bg-light']);
            echo html_writer::start_div('d-flex align-items-center justify-content-between');
            echo html_writer::tag('span', s($room->name));
            if ($isonlineroom) {
                echo html_writer::tag('span', 'ONLINE', ['class' => 'badge online-room-badge ms-1', 'style' => 'font-size: 9px;']);
            }
            echo html_writer::end_div();
            echo html_writer::end_tag('td');

            // Time Slots Columns
            foreach ($timeblocks as $tb) {
                if (isset($breakwindows[$tb])) {
                    if ($ridx === 0) {
                        $tbstart = explode(' - ', $tb)[0];
                        $startsec = strtotime("2026-01-01 " . $tbstart);
                        $is_lunch = ($startsec && $startsec >= strtotime("2026-01-01 11:30"));
                        $breaktext = $is_lunch ? "L U N C H   B R E A K" : "T E A   B R E A K";

                        echo html_writer::tag('td', '<div class="break-column-vertical">' . $breaktext . '</div>', [
                            'rowspan' => $numrooms,
                            'class' => 'break-column-strip text-center p-0 align-middle'
                        ]);
                    }
                    // For $ridx > 0, break cell is covered by rowspan, do not render td!
                } else {
                    echo html_writer::start_tag('td', ['class' => 'p-2 align-middle text-center']);
                    $entries = $matrix[$daynum][$room->id][$tb] ?? [];
                    if (empty($entries)) {
                        echo '<span class="text-muted small">&mdash;</span>';
                    } else {
                        foreach ($entries as $entry) {
                            $teacher = (!empty($entry->firstname) || !empty($entry->lastname)) ? fullname($entry) : 'Unassigned';
                            $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $entry->id]);
                            $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $entry->id, 'sesskey' => sesskey()]);
                            $typebadge = ($entry->schedule_type === 'exam')
                                ? '<span class="badge att-badge-exam me-1">EXAM</span>'
                                : '<span class="badge bg-secondary text-dark me-1" style="font-size:9px; background:#f1f5f9 !important; border:1px solid #cbd5e1 !important;">Lec</span>';

                            echo html_writer::start_div('grid-cell-card schola-entry-card text-start mb-1');
                            echo html_writer::start_div('d-flex justify-content-between align-items-center mb-1');
                            echo html_writer::tag('span', s($entry->coursecode), ['class' => 'grid-cell-course']);
                            echo $typebadge;
                            echo html_writer::end_div();
                            echo html_writer::tag('div', s($teacher), ['class' => 'grid-cell-lecturer mb-1']);
                            echo html_writer::start_div('cell-action-buttons gap-1 border-top pt-1 mt-1');
                            echo html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary py-0 px-1.5 extra-small']);
                            echo html_writer::link($delurl, 'Delete', [
                                'class' => 'btn btn-sm btn-outline-danger py-0 px-1.5 extra-small',
                                'onclick' => 'return confirm("Delete allocation?");',
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
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// -------------------------------------------------------------------
// Manage Breaks Modal
// -------------------------------------------------------------------
?>
<div class="modal fade" id="manageBreaksModal" tabindex="-1" aria-labelledby="manageBreaksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="manageBreaksModalLabel">
          <i class="fa fa-clock-o text-success me-2"></i>Institutional Break Window Manager
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="slots.php">
        <input type="hidden" name="action" value="build_bell_schedule">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="redirect_to" value="schedules.php">
        <div class="modal-body py-4">
          <p class="text-muted small mb-4">Customize tea & lunch break windows for the matrix timetable. Time slots will be automatically recalculated.</p>

          <div class="card p-3 bg-light border-0 mb-3 rounded-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fa fa-coffee text-warning me-1"></i> Morning Tea Break</h6>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">Start Time</label>
                <input type="time" name="tea_start" value="10:00" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">End Time</label>
                <input type="time" name="tea_end" value="10:30" class="form-control form-control-sm">
              </div>
            </div>
          </div>

          <div class="card p-3 bg-light border-0 mb-3 rounded-3">
            <h6 class="fw-bold text-dark mb-2"><i class="fa fa-cutlery text-success me-1"></i> Afternoon Lunch Break</h6>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">Start Time</label>
                <input type="time" name="lunch_start" value="12:30" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold text-muted">End Time</label>
                <input type="time" name="lunch_end" value="13:30" class="form-control form-control-sm">
              </div>
            </div>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Day Start Time</label>
              <input type="time" name="day_start" value="08:00" class="form-control form-control-sm">
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Day End Time</label>
              <input type="time" name="day_end" value="17:00" class="form-control form-control-sm">
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill font-weight-bold px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald font-weight-bold px-4">Apply Break Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterTimetableEntries() {
    var query = document.getElementById("scholaLiveSearch").value.toLowerCase().trim();
    var cards = document.querySelectorAll(".schola-entry-card");
    cards.forEach(function(card) {
        var text = card.innerText.toLowerCase();
        if (!query || text.indexOf(query) !== -1) {
            card.style.display = "";
        } else {
            card.style.display = "none";
        }
    });
}

function toggleEditMode(e) {
    if (e) e.preventDefault();
    var container = document.getElementById("institutionalGridContainer");
    var btn = document.getElementById("enableEditModeBtn");
    if (!container || !btn) return;
    
    if (container.classList.contains("edit-mode-active")) {
        container.classList.remove("edit-mode-active");
        btn.className = "btn btn-outline-emerald d-inline-flex align-items-center";
        btn.innerHTML = '<i class="fa fa-pencil me-1"></i> Enable Edit Mode';
    } else {
        container.classList.add("edit-mode-active");
        btn.className = "btn btn-emerald d-inline-flex align-items-center";
        btn.innerHTML = '<i class="fa fa-check me-1"></i> Disable Edit Mode';
    }
}
<?php if (!empty($openbreaks)): ?>
document.addEventListener("DOMContentLoaded", function() {
    var modalElem = document.getElementById("manageBreaksModal");
    if (modalElem && typeof bootstrap !== "undefined") {
        var myModal = new bootstrap.Modal(modalElem);
        myModal.show();
    }
});
<?php endif; ?>
</script>
<?php

echo $OUTPUT->footer();

