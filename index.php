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
 * Main admin dashboard and timetable generation view for local_academic_timetabler.
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

$PAGE->set_url(new moodle_url('/local/academic_timetabler/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('pluginname', 'local_academic_timetabler'));

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'generate' && confirm_sesskey()) {
    global $DB;
    \core_php_time_limit::raise(600);
    raise_memory_limit(MEMORY_EXTRA);

    $scheduletype = optional_param('scheduletype', 'class', PARAM_ALPHA);
    $categoryid   = optional_param('categoryid', 0, PARAM_INT);
    $genmode      = optional_param('mode', 'overwrite', PARAM_ALPHA);

    // Build course query based on category scope
    $params = [];
    $select = 'id > 1 AND visible = 1';
    if ($categoryid > 0) {
        $select .= ' AND category = :categoryid';
        $params['categoryid'] = $categoryid;
    }

    $courses = $DB->get_records_select('course', $select, $params, 'id ASC');
    $slots   = $DB->get_records('local_att_slots');
    $rooms   = $DB->get_records('local_att_rooms');

    if (empty($rooms)) {
        redirect(
            new moodle_url('/local/academic_timetabler/rooms.php'),
            'Please configure at least one room before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if (empty($slots)) {
        redirect(
            new moodle_url('/local/academic_timetabler/slots.php'),
            'Please configure time slots before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if (empty($courses)) {
        redirect(
            new moodle_url('/local/academic_timetabler/index.php'),
            'No active courses found in the selected department category.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    // Strict License Plan Capacity & Feature Enforcement
    $coursecount = count($courses);
    $maxcourses = \local_academic_timetabler\licensing\license_manager::get_max_courses();
    $tiername = \local_academic_timetabler\licensing\license_manager::get_tier_name();

    if ($maxcourses > 0 && $coursecount > $maxcourses) {
        redirect(
            new moodle_url('/local/academic_timetabler/index.php'),
            "License Capacity Exceeded: Your institution has {$coursecount} active courses, but your {$tiername} plan is limited to {$maxcourses} courses. Please upgrade to Pro University ($499/year) to unlock unlimited course scheduling.",
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    if ($scheduletype === 'exam' && !\local_academic_timetabler\licensing\license_manager::can_solve_exams()) {
        redirect(
            new moodle_url('/local/academic_timetabler/index.php'),
            "Examination Timetabling Feature Locked: Examination schedule generation requires a Starter or Pro University plan. Please upgrade your license key to unlock exam scheduling.",
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    try {
        $solver = new \local_academic_timetabler\algorithm\solver($slots, $rooms);
        $solver->set_slot_type($scheduletype);
        $solver->load_courses($courses);

        if ($genmode === 'append') {
            // Load ALL existing schedule entries as hard occupied blockouts
            $existingschedules = $DB->get_records('local_att_schedules');
            $solver->load_existing_schedules($existingschedules);
        } else {
            // Overwrite mode: Delete matching schedule type / category entries
            if ($categoryid > 0) {
                $catcourseids = array_keys($courses);
                if (!empty($catcourseids)) {
                    list($insql, $inparams) = $DB->get_in_or_equal($catcourseids, SQL_PARAMS_NAMED);
                    $inparams['stype'] = $scheduletype;
                    $DB->delete_records_select('local_att_schedules', "schedule_type = :stype AND courseid {$insql}", $inparams);
                }
            } else {
                $DB->delete_records('local_att_schedules', ['schedule_type' => $scheduletype]);
            }

            // Load remaining non-deleted schedules (e.g. Class schedules when generating Exams) as occupied blockouts
            $othersexisting = $DB->get_records_select('local_att_schedules', 'schedule_type != :stype', ['stype' => $scheduletype]);
            $solver->load_existing_schedules($othersexisting);
        }

        if ($solver->solve_all()) {
            $solution = $solver->get_solution();
            $count = 0;
            foreach ($solution['classes'] ?? [] as $assignedkey => $sched) {
                if (strpos((string)$assignedkey, 'existing_') === 0) {
                    continue; // Skip loaded blockout entries
                }
                $courseid = $sched['course_id'] ?? (int)$assignedkey;
                $DB->insert_record('local_att_schedules', (object)[
                    'schedule_type' => $scheduletype,
                    'courseid'     => $courseid,
                    'roomid'       => $sched['room_id'],
                    'slotid'       => $sched['slot_id'],
                    'teacherid'    => $sched['teacher_id'],
                ]);
                $count++;
            }
            $label = ($scheduletype === 'exam') ? 'Examination' : 'Class';
            redirect(
                new moodle_url('/local/academic_timetabler/schedules.php', ['type' => $scheduletype, 'categoryid' => $categoryid]),
                "{$label} Timetable generated successfully! {$count} course sessions assigned conflict-free.",
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                new moodle_url('/local/academic_timetabler/index.php'),
                "Notice: Solver could not assign all courses without conflicts. Try adding more rooms or time slots.",
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/local/academic_timetabler/index.php'),
            "Error running timetable generator: " . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
echo \local_academic_timetabler\output\renderer::render_nav_header('index');

$output = $PAGE->get_renderer('local_academic_timetabler');
echo $output->render_dashboard([]);

// -------------------------------------------------------------------
// Multi-Timetable Generator Options Card
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm my-4 bg-white');
echo html_writer::div(html_writer::tag('h5', 'Automated CSP Solver & Timetable Generator', ['class' => 'mb-0 font-weight-bold']), 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/academic_timetabler/index.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'generate']);

// Fetch course categories (departments)
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$catoptions = [0 => '-- Entire Institution (All Departments) --'] + $categories;

$typeoptions = [
    'class' => 'Regular Semester Class Schedule',
    'exam'  => 'Examination Schedule',
];

$modeoptions = [
    'overwrite' => 'Overwrite Existing Timetables of Selected Type',
    'append'    => 'Append Mode (Preserve Existing Timetables & Schedule Around Them)',
];

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', 'Timetable Profile / Type', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($typeoptions, 'scheduletype', 'class', false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', 'Department / Course Category Scope', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($catoptions, 'categoryid', 0, false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', 'Generation & Conflict Mode', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($modeoptions, 'mode', 'overwrite', false, ['class' => 'form-select p-2']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::tag('button', 'Run Solver & Generate Timetable', ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm fs-6']);
echo html_writer::tag('span', 'Cross-schedule conflict prevention will automatically protect active venue and instructor bookings.', ['class' => 'text-muted small']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
