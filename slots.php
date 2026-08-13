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
 * Manage Bell Schedule & Time Slots for local_academic_timetabler.
 * Designed for Senior School Administrators with a Guided Bell Schedule Wizard.
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

$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/academic_timetabler/slots.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Bell Schedule & Time Slots');
$PAGE->set_heading('Bell Schedule & Time Slots');

// -------------------------------------------------------------------
// Action: Delete Single Slot
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_att_slots', ['id' => $id]);
    redirect($url, 'Time slot deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Clear All Slots
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    $DB->delete_records('local_att_slots');
    redirect($url, 'All time slots cleared successfully.');
}

// -------------------------------------------------------------------
// Action: Guided Bell Schedule Wizard Computation
// -------------------------------------------------------------------
if ($action === 'build_bell_schedule' && confirm_sesskey()) {
    $daystart     = optional_param('day_start', '08:00', PARAM_TEXT);
    $dayend       = optional_param('day_end', '17:00', PARAM_TEXT);
    $periodmins   = optional_param('period_minutes', 60, PARAM_INT);
    $teastart     = optional_param('tea_start', '10:00', PARAM_TEXT);
    $teaend       = optional_param('tea_end', '10:30', PARAM_TEXT);
    $lunchstart   = optional_param('lunch_start', '12:30', PARAM_TEXT);
    $lunchend     = optional_param('lunch_end', '13:30', PARAM_TEXT);
    $activedays   = optional_param_array('days', [1, 2, 3, 4, 5], PARAM_INT);
    $wipeexisting = optional_param('wipe_existing', 1, PARAM_INT);

    if ($wipeexisting) {
        $DB->delete_records('local_att_slots');
    }

    $inserted = 0;

    foreach ($activedays as $day) {
        $currsec = strtotime("2026-01-01 " . $daystart);
        $endsec  = strtotime("2026-01-01 " . $dayend);

        $tstartsec = strtotime("2026-01-01 " . $teastart);
        $tendsec   = strtotime("2026-01-01 " . $teaend);
        $lstartsec = strtotime("2026-01-01 " . $lunchstart);
        $lendsec   = strtotime("2026-01-01 " . $lunchend);

        while ($currsec < $endsec) {
            // Check Morning Tea Break
            if ($tstartsec && $tendsec && $currsec >= $tstartsec && $currsec < $tendsec) {
                $DB->insert_record('local_att_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => date('H:i', $tstartsec),
                    'endtime'   => date('H:i', $tendsec),
                    'type'      => 'break',
                ]);
                $inserted++;
                $currsec = $tendsec;
                continue;
            }

            // Check Lunch Break
            if ($lstartsec && $lendsec && $currsec >= $lstartsec && $currsec < $lendsec) {
                $DB->insert_record('local_att_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => date('H:i', $lstartsec),
                    'endtime'   => date('H:i', $lendsec),
                    'type'      => 'break',
                ]);
                $inserted++;
                $currsec = $lendsec;
                continue;
            }

            // Standard Lesson Period
            $nextsec = $currsec + ($periodmins * 60);
            if ($nextsec > $endsec) {
                break;
            }

            // If lesson overlaps tea start
            if ($tstartsec && $currsec < $tstartsec && $nextsec > $tstartsec) {
                $nextsec = $tstartsec;
            }
            // If lesson overlaps lunch start
            if ($lstartsec && $currsec < $lstartsec && $nextsec > $lstartsec) {
                $nextsec = $lstartsec;
            }

            $DB->insert_record('local_att_slots', (object)[
                'dayofweek' => $day,
                'starttime' => date('H:i', $currsec),
                'endtime'   => date('H:i', $nextsec),
                'type'      => 'class',
            ]);
            $inserted++;
            $currsec = $nextsec;
        }
    }

    redirect($url, "Bell Schedule applied successfully! {$inserted} time windows generated across " . count($activedays) . " operating days.");
}

// -------------------------------------------------------------------
// Action: Quick Executive Presets
// -------------------------------------------------------------------
if ($action === 'preset' && confirm_sesskey()) {
    $profile = optional_param('profile', 'univ_60', PARAM_TEXT);
    $DB->delete_records('local_att_slots');
    $inserted = 0;

    for ($day = 1; $day <= 5; $day++) {
        if ($profile === 'highschool_45') {
            $templates = [
                ['08:00', '08:45', 'class'], ['08:45', '09:30', 'class'],
                ['09:30', '10:15', 'class'], ['10:15', '10:45', 'break'], // Tea
                ['10:45', '11:30', 'class'], ['11:30', '12:15', 'class'],
                ['12:15', '13:15', 'break'], // Lunch
                ['13:15', '14:00', 'class'], ['14:00', '14:45', 'class'], ['14:45', '15:30', 'class'],
            ];
        } else if ($profile === 'univ_180') {
            $templates = [
                ['08:00', '11:00', 'class'],
                ['11:00', '13:00', 'break'], // Break / Lunch
                ['13:00', '16:00', 'class'],
                ['16:00', '19:00', 'class'],
            ];
        } else if ($profile === 'univ_90') {
            $templates = [
                ['08:00', '09:30', 'class'], ['09:30', '11:00', 'class'],
                ['11:00', '11:30', 'break'], // Tea
                ['11:30', '13:00', 'class'], ['13:00', '14:00', 'break'], // Lunch
                ['14:00', '15:30', 'class'], ['15:30', '17:00', 'class'],
            ];
        } else if ($profile === 'exam_3h') {
            $templates = [
                ['08:30', '11:30', 'exam'],
                ['11:30', '13:30', 'break'],
                ['13:30', '16:30', 'exam'],
            ];
        } else if ($profile === 'evening') {
            $templates = [
                ['17:30', '19:00', 'class'],
                ['19:00', '19:30', 'break'],
                ['19:30', '21:00', 'class'],
            ];
        } else {
            // univ_60 default
            $templates = [
                ['08:00', '09:00', 'class'], ['09:00', '10:00', 'class'],
                ['10:00', '10:30', 'break'], // Tea
                ['10:30', '11:30', 'class'], ['11:30', '12:30', 'class'],
                ['12:30', '13:30', 'break'], // Lunch
                ['13:30', '14:30', 'class'], ['14:30', '15:30', 'class'], ['15:30', '16:30', 'class'],
            ];
        }

        foreach ($templates as $t) {
            $DB->insert_record('local_att_slots', (object)[
                'dayofweek' => $day,
                'starttime' => $t[0],
                'endtime'   => $t[1],
                'type'      => $t[2],
            ]);
            $inserted++;
        }
    }

    redirect($url, "Executive Profile '{$profile}' loaded! {$inserted} time windows generated for Mon-Fri.");
}

// -------------------------------------------------------------------
// Action: Save / Edit Single Slot
// -------------------------------------------------------------------
$editslot = null;
if ($action === 'edit' && $id > 0) {
    $editslot = $DB->get_record('local_att_slots', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('save_slot', 0, PARAM_INT)) {
    $editslotid = optional_param('slotid', 0, PARAM_INT);
    $dayofweek  = optional_param('dayofweek', 1, PARAM_INT);
    $starttime  = optional_param('starttime', '', PARAM_TEXT);
    $endtime    = optional_param('endtime', '', PARAM_TEXT);
    $type       = optional_param('type', 'class', PARAM_ALPHA);

    if (!empty($starttime) && !empty($endtime)) {
        $record = (object)[
            'type'      => $type,
            'dayofweek' => $dayofweek,
            'starttime' => $starttime,
            'endtime'   => $endtime,
        ];

        if ($editslotid > 0) {
            $record->id = $editslotid;
            $DB->update_record('local_att_slots', $record);
            redirect($url, 'Time slot updated successfully.');
        } else {
            $DB->insert_record('local_att_slots', $record);
            redirect($url, 'Time slot added successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_academic_timetabler\output\renderer::render_nav_header('slots');

// -------------------------------------------------------------------
// Card 1: 1-Click School Profile Presets
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white');
echo html_writer::div(html_writer::tag('h5', 'Quick School Schedule Profiles', ['class' => 'mb-0 font-weight-bold']), 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::div('Select an official school structure profile to instantly configure your institution\'s weekly timetabling slots:', 'text-muted mb-3');

echo html_writer::start_div('d-flex flex-wrap gap-2');

$profiles = [
    'univ_60'        => ['label' => 'University Standard (60-Min Periods)', 'class' => 'btn-primary'],
    'univ_180'       => ['label' => 'University 3-Hour Block Lectures', 'class' => 'btn-success text-white'],
    'highschool_45' => ['label' => 'High School (45-Min Periods)', 'class' => 'btn-info text-dark'],
    'univ_90'        => ['label' => 'Modular University (90-Min Periods)', 'class' => 'btn-secondary'],
    'exam_3h'        => ['label' => 'Examination Season (3-Hr Blocks)', 'class' => 'btn-warning text-dark'],
    'evening'        => ['label' => 'Evening / Part-Time Program', 'class' => 'btn-dark'],
];

foreach ($profiles as $pkey => $pinfo) {
    $purl = new moodle_url($url, ['action' => 'preset', 'profile' => $pkey, 'sesskey' => sesskey()]);
    echo html_writer::link($purl, $pinfo['label'], [
        'class' => 'btn ' . $pinfo['class'] . ' font-weight-bold px-3 py-2 shadow-sm',
        'onclick' => "return confirm('Load profile \"{$pinfo['label']}\"? This will configure active slots for Mon-Fri.');",
    ]);
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Card 2: Guided Bell Schedule Wizard
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white');
echo html_writer::div(html_writer::tag('h5', 'Guided Bell Schedule Wizard', ['class' => 'mb-0 font-weight-bold text-dark']), 'card-header bg-light p-3 border-bottom');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'build_bell_schedule']);

echo html_writer::start_div('row g-3');

// School Hours & Period Length
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day Start Time', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_start', 'value' => '08:00', 'class' => 'form-control p-2', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day End Time', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_end', 'value' => '17:00', 'class' => 'form-control p-2', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', 'Lesson / Period Duration', ['class' => 'form-label font-weight-bold text-dark']);
$periodoptions = [
    45  => '45 Minutes (Secondary / High School Period)',
    50  => '50 Minutes (Standard School Hour)',
    60  => '60 Minutes (Standard University Lecture)',
    90  => '90 Minutes (Extended Modular Lecture)',
    120 => '120 Minutes (2-Hour Double Period)',
    180 => '180 Minutes (3-Hour Block Lecture)',
];
echo html_writer::select($periodoptions, 'period_minutes', 60, false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

// Break Windows
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Morning Tea Break Start', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_start', 'value' => '10:00', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Morning Tea Break End', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_end', 'value' => '10:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Lunch Break Start', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_start', 'value' => '12:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Lunch Break End', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_end', 'value' => '13:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

// Active Days Selection
echo html_writer::start_div('col-12 mt-3');
echo html_writer::tag('label', 'Operating School Days', ['class' => 'form-label font-weight-bold text-dark d-block']);
$dayslist = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
echo html_writer::start_div('d-flex gap-3 flex-wrap');
foreach ($dayslist as $dnum => $dname) {
    $checked = ($dnum <= 5);
    echo html_writer::start_div('form-check form-check-inline');
    echo html_writer::checkbox('days[]', $dnum, $checked, ' ' . $dname, ['class' => 'form-check-input', 'id' => 'day_chk_' . $dnum]);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row

echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between');
echo html_writer::checkbox('wipe_existing', '1', true, ' Wipe existing time slots before applying', ['class' => 'form-check-input text-muted']);
echo html_writer::tag('button', 'Apply Bell Schedule', ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Card 3: Add / Edit Single Custom Slot
// -------------------------------------------------------------------
if ($editslot) {
    echo html_writer::start_div('card shadow-sm mb-4 border-primary');
    echo html_writer::div(html_writer::tag('h5', 'Edit Time Slot Window', ['class' => 'mb-0 font-weight-bold text-primary']), 'card-header bg-light');
    echo html_writer::start_div('card-body');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save_slot', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slotid', 'value' => $editslot->id]);

    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    $slottypes = [
        'class' => 'Class Lecture (Standard Teaching Window)',
        'lab'   => 'Laboratory Practical (Extended Block)',
        'break' => 'Break / Blockout (Lunch, Tea Break - Excluded from Solver)',
        'exam'  => 'Examination Period (Dedicated Exam Block)',
    ];

    echo html_writer::start_div('row g-3');
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Day of Week', ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($days, 'dayofweek', $editslot->dayofweek, false, ['class' => 'form-select']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', 'Start Time', ['class' => 'form-label font-weight-bold']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'starttime', 'class' => 'form-control', 'value' => s($editslot->starttime), 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', 'End Time', ['class' => 'form-label font-weight-bold']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'endtime', 'class' => 'form-control', 'value' => s($editslot->endtime), 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-5');
    echo html_writer::tag('label', 'Slot Type / Category', ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($slottypes, 'type', $editslot->type, false, ['class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('mt-3 d-flex gap-2');
    echo html_writer::tag('button', 'Update Time Slot', ['type' => 'submit', 'class' => 'btn btn-primary font-weight-bold']);
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-outline-secondary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// -------------------------------------------------------------------
// Visual Daily Bell Schedule Summary Table
// -------------------------------------------------------------------
$slots = $DB->get_records('local_att_slots', null, 'dayofweek ASC, starttime ASC');

echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2');
echo html_writer::tag('h4', 'Active Bell Schedule Windows (' . count($slots) . ' Time Slots)', ['class' => 'mb-0 font-weight-bold']);

if (!empty($slots)) {
    $clearallurl = new moodle_url($url, ['action' => 'clearall', 'sesskey' => sesskey()]);
    echo html_writer::link($clearallurl, 'Clear All Slots', [
        'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
        'onclick' => 'return confirm("Clear all configured time slots?");',
    ]);
}
echo html_writer::end_div();

if (empty($slots)) {
    echo html_writer::div('No bell schedule time windows configured yet. Use the <strong>Guided Bell Schedule Wizard</strong> or select a <strong>Quick School Profile</strong> above to get started instantly.', 'alert alert-info shadow-sm p-4 text-center fs-6');
} else {
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    $table = new html_table();
    $table->head = ['ID', 'Day', 'Time Window', 'Functional Category', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle bg-white shadow-sm'];

    foreach ($slots as $slot) {
        $dayname = $days[$slot->dayofweek] ?? ('Day ' . $slot->dayofweek);
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $slot->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $slot->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("Delete this time slot?");',
        ]);

        $typebadge = match ($slot->type) {
            'break' => '<span class="badge bg-secondary text-white px-2 py-1 fs-7">INSTITUTIONAL BREAK / BLOCKOUT</span>',
            'lab'   => '<span class="badge bg-info text-dark px-2 py-1 fs-7">LABORATORY PRACTICAL</span>',
            'exam'  => '<span class="badge bg-warning text-dark px-2 py-1 fs-7">EXAMINATION PERIOD</span>',
            default => '<span class="badge bg-primary text-white px-2 py-1 fs-7">CLASS LECTURE</span>',
        };

        $table->data[] = [
            $slot->id,
            '<strong>' . $dayname . '</strong>',
            s($slot->starttime) . ' &mdash; ' . s($slot->endtime),
            $typebadge,
            $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
