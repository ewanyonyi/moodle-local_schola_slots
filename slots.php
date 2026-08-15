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
 * Manage Bell Schedule & Time Slots for local_schola_slots.
 * Designed for Senior School Administrators with customizable institutional schedule profiles.
 *
 * @package     local_schola_slots
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_schola_slots\profile_manager;

require_login();
$context = context_system::instance();
require_capability('local/schola_slots:manage', $context);

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$id     = optional_param('id', 0, PARAM_INT);
$pkey   = optional_param('profile', '', PARAM_ALPHANUMEXT);

$url = new moodle_url('/local/schola_slots/slots.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Bell Schedule & Time Slots');
$PAGE->set_heading('Bell Schedule & Time Slots');

// -------------------------------------------------------------------
// Action: Delete Single Time Slot
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_slots_slots', ['id' => $id]);
    redirect($url, 'Time slot deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Clear All Slots
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    $DB->delete_records('local_schola_slots_slots');
    redirect($url, 'All time slots cleared successfully.');
}

// -------------------------------------------------------------------
// Action: Apply Executive / School Schedule Profile
// -------------------------------------------------------------------
if ($action === 'preset' && !empty($pkey) && confirm_sesskey()) {
    $profile = profile_manager::get_profile($pkey);
    if ($profile) {
        $count = profile_manager::apply_profile($pkey);
        $pname = s($profile['name']);
        redirect($url, "Schedule Profile '{$pname}' loaded successfully! {$count} active time slots generated.");
    } else {
        redirect($url, 'Invalid profile key specified.', null, \core\output\notification::NOTIFY_ERROR);
    }
}

// -------------------------------------------------------------------
// Action: Save / Update Schedule Profile Configuration
// -------------------------------------------------------------------
if ($action === 'save_profile' && confirm_sesskey() && data_submitted()) {
    $key         = optional_param('key', '', PARAM_ALPHANUMEXT);
    $name        = optional_param('name', '', PARAM_TEXT);
    $badge       = optional_param('badge', '', PARAM_TEXT);
    $theme       = optional_param('theme', 'primary', PARAM_ALPHA);
    $icon        = optional_param('icon', 'fa-graduation-cap', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);
    $daystart    = optional_param('day_start', '08:00', PARAM_TEXT);
    $dayend      = optional_param('day_end', '17:00', PARAM_TEXT);
    $periodmins  = optional_param('period_minutes', 60, PARAM_INT);
    $teastart    = optional_param('tea_start', '', PARAM_TEXT);
    $teaend      = optional_param('tea_end', '', PARAM_TEXT);
    $lunchstart  = optional_param('lunch_start', '', PARAM_TEXT);
    $lunchend    = optional_param('lunch_end', '', PARAM_TEXT);
    $activedays  = optional_param_array('days', [1, 2, 3, 4, 5], PARAM_INT);
    $applynow    = optional_param('apply_now', 0, PARAM_INT);

    if (empty($key)) {
        $key = 'custom_' . time();
    }

    if (!empty($name)) {
        $profiledata = [
            'key'            => $key,
            'name'           => $name,
            'badge'          => $badge ?: "{$periodmins}-Min Periods",
            'theme'          => $theme,
            'icon'           => $icon,
            'description'    => $description,
            'day_start'      => $daystart,
            'day_end'        => $dayend,
            'period_minutes' => $periodmins,
            'tea_start'      => $teastart,
            'tea_end'        => $teaend,
            'lunch_start'    => $lunchstart,
            'lunch_end'      => $lunchend,
            'days'           => array_map('intval', $activedays),
            'is_default'     => false,
        ];

        profile_manager::save_profile($key, $profiledata);

        $msg = "Schedule Profile '{$name}' saved successfully.";
        if ($applynow) {
            $count = profile_manager::apply_profile($key);
            $msg .= " Applied immediately with {$count} generated time slots.";
        }

        redirect($url, $msg);
    }
}

// -------------------------------------------------------------------
// Action: Reset Profiles to System Defaults
// -------------------------------------------------------------------
if ($action === 'reset_profiles' && confirm_sesskey()) {
    profile_manager::reset_defaults();
    redirect($url, 'Schedule profiles reset to system default institutional profiles.');
}

// -------------------------------------------------------------------
// Action: Delete Custom Profile
// -------------------------------------------------------------------
if ($action === 'delete_profile' && !empty($pkey) && confirm_sesskey()) {
    profile_manager::delete_profile($pkey);
    redirect($url, 'Custom schedule profile removed.');
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
        $DB->delete_records('local_schola_slots_slots');
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
                $DB->insert_record('local_schola_slots_slots', (object)[
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
                $DB->insert_record('local_schola_slots_slots', (object)[
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

            if ($tstartsec && $currsec < $tstartsec && $nextsec > $tstartsec) {
                $nextsec = $tstartsec;
            }
            if ($lstartsec && $currsec < $lstartsec && $nextsec > $lstartsec) {
                $nextsec = $lstartsec;
            }

            $DB->insert_record('local_schola_slots_slots', (object)[
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
// Action: Save / Edit Single Time Slot
// -------------------------------------------------------------------
$editslot = null;
if ($action === 'edit' && $id > 0) {
    $editslot = $DB->get_record('local_schola_slots_slots', ['id' => $id]);
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
            $DB->update_record('local_schola_slots_slots', $record);
            redirect($url, 'Time slot updated successfully.');
        } else {
            $DB->insert_record('local_schola_slots_slots', $record);
            redirect($url, 'Time slot added successfully.');
        }
    }
}

// -------------------------------------------------------------------
// Prepare Editing Profile Context (if edit_profile or add_profile requested)
// -------------------------------------------------------------------
$editingprofile = null;
if (($action === 'edit_profile' || $action === 'add_profile') && (!empty($pkey) || $action === 'add_profile')) {
    if (!empty($pkey)) {
        $editingprofile = profile_manager::get_profile($pkey);
    }
    if (!$editingprofile) {
        $editingprofile = [
            'key'            => 'profile_' . time(),
            'name'           => '',
            'badge'          => '60-Min Periods',
            'theme'          => 'primary',
            'icon'           => 'fa-graduation-cap',
            'description'    => '',
            'day_start'      => '08:00',
            'day_end'        => '17:00',
            'period_minutes' => 60,
            'tea_start'      => '10:00',
            'tea_end'        => '10:30',
            'lunch_start'    => '12:30',
            'lunch_end'      => '13:30',
            'days'           => [1, 2, 3, 4, 5],
            'is_default'     => false,
        ];
    }
}

echo $OUTPUT->header();

echo \local_schola_slots\output\renderer::render_nav_header('slots');

// -------------------------------------------------------------------
// Form View: Edit / Create Schedule Profile Form Card
// -------------------------------------------------------------------
if ($editingprofile) {
    $formtitle = !empty($editingprofile['name'])
        ? 'Edit Schedule Profile: ' . s($editingprofile['name'])
        : 'Create Custom Institutional Schedule Profile';

    echo html_writer::start_div('card border-0 shadow-lg mb-4 bg-white rounded-3');
    echo html_writer::start_div('card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between');
    echo html_writer::tag('h5', '<i class="fa fa-pen-to-square me-2"></i>' . $formtitle, ['class' => 'mb-0 font-weight-bold']);
    echo html_writer::link($url, '&times; Close Editor', ['class' => 'btn btn-sm btn-outline-light']);
    echo html_writer::end_div();

    echo html_writer::start_div('card-body p-4');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save_profile']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'key', 'value' => s($editingprofile['key'])]);

    echo html_writer::start_div('row g-3 mb-3');

    // Profile Title
    echo html_writer::start_div('col-md-5');
    echo html_writer::tag('label', 'Profile Name / Title', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'name', 'value' => s($editingprofile['name']),
        'class' => 'form-control p-2', 'placeholder' => 'e.g. University Standard', 'required' => 'required'
    ]);
    echo html_writer::end_div();

    // Badge Label
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Badge Subtitle', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'badge', 'value' => s($editingprofile['badge'] ?? ''),
        'class' => 'form-control p-2', 'placeholder' => 'e.g. 60-Min Periods'
    ]);
    echo html_writer::end_div();

    // Color Theme
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', 'Color Theme', ['class' => 'form-label font-weight-bold text-dark']);
    $themeoptions = [
        'primary' => 'Primary Blue',
        'success' => 'Emerald Green',
        'info'    => 'Teal / Cyan',
        'purple'  => 'Modular Purple',
        'warning' => 'Amber / Gold',
        'dark'    => 'Night Dark',
    ];
    echo html_writer::select($themeoptions, 'theme', $editingprofile['theme'] ?? 'primary', false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Icon Selection
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', 'Profile Icon', ['class' => 'form-label font-weight-bold text-dark']);
    $iconoptions = [
        'fa-graduation-cap'  => '🎓 Graduation Cap',
        'fa-building-columns' => '🏛️ Executive Building',
        'fa-school'           => '🏫 School House',
        'fa-cubes'            => '⚡ Modular Cubes',
        'fa-file-signature'  => '📝 Examination',
        'fa-moon'            => '🌙 Evening Moon',
        'fa-clock'           => '⏱️ Standard Clock',
    ];
    echo html_writer::select($iconoptions, 'icon', $editingprofile['icon'] ?? 'fa-graduation-cap', false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Description
    echo html_writer::start_div('col-12');
    echo html_writer::tag('label', 'Profile Description', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'description', 'value' => s($editingprofile['description'] ?? ''),
        'class' => 'form-control p-2', 'placeholder' => 'e.g. Standard 60-minute university lecture blocks with morning tea and lunch breaks.'
    ]);
    echo html_writer::end_div();

    // Day Start & End
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Daily Day Start Time', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_start', 'value' => s($editingprofile['day_start'] ?? '08:00'), 'class' => 'form-control p-2', 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Daily Day End Time', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_end', 'value' => s($editingprofile['day_end'] ?? '17:00'), 'class' => 'form-control p-2', 'required' => 'required']);
    echo html_writer::end_div();

    // Period Duration
    echo html_writer::start_div('col-md-6');
    echo html_writer::tag('label', 'Period / Lecture Duration', ['class' => 'form-label font-weight-bold text-dark']);
    $periodoptions = [
        45  => '45 Minutes (Secondary / High School Period)',
        50  => '50 Minutes (Standard School Hour)',
        60  => '60 Minutes (Standard University Lecture)',
        90  => '90 Minutes (Extended Modular Block)',
        120 => '120 Minutes (2-Hour Double Period)',
        180 => '180 Minutes (3-Hour Block Lecture)',
    ];
    echo html_writer::select($periodoptions, 'period_minutes', (int)($editingprofile['period_minutes'] ?? 60), false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Tea & Lunch Breaks
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Tea Break Start (Optional)', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_start', 'value' => s($editingprofile['tea_start'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Tea Break End (Optional)', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_end', 'value' => s($editingprofile['tea_end'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Lunch Break Start (Optional)', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_start', 'value' => s($editingprofile['lunch_start'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', 'Lunch Break End (Optional)', ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_end', 'value' => s($editingprofile['lunch_end'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    // Active Days
    echo html_writer::start_div('col-12 mt-2');
    echo html_writer::tag('label', 'Operating School Days', ['class' => 'form-label font-weight-bold text-dark d-block']);
    $dayslist = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $activedays = $editingprofile['days'] ?? [1, 2, 3, 4, 5];
    echo html_writer::start_div('d-flex gap-3 flex-wrap');
    foreach ($dayslist as $dnum => $dname) {
        $checked = in_array($dnum, $activedays);
        echo html_writer::start_div('form-check form-check-inline');
        echo html_writer::checkbox('days[]', $dnum, $checked, ' ' . $dname, ['class' => 'form-check-input', 'id' => 'profile_day_' . $dnum]);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // row

    // Submit actions
    echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
    echo html_writer::start_div('form-check d-flex align-items-center gap-2 mb-0');
    echo html_writer::checkbox('apply_now', '1', true, ' Apply this profile to active time slots immediately upon saving', ['class' => 'form-check-input me-1', 'id' => 'chk_apply_now']);
    echo html_writer::end_div();

    echo html_writer::start_div('d-flex gap-2');
    echo html_writer::tag('button', 'Save Profile', ['type' => 'submit', 'class' => 'btn btn-primary font-weight-bold px-4 py-2 shadow-sm']);
    echo html_writer::link($url, 'Cancel', ['class' => 'btn btn-outline-secondary px-3 py-2']);
    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// -------------------------------------------------------------------
// Component: Professional Quick School Schedule Profiles Gallery
// -------------------------------------------------------------------
$profiles = profile_manager::get_profiles();

echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white rounded-3');
echo html_writer::start_div('card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::start_div('d-flex align-items-center gap-2');
echo html_writer::tag('h5', 'Institutional Schedule Profiles', ['class' => 'mb-0 font-weight-bold']);
echo html_writer::tag('span', count($profiles) . ' Profiles', ['class' => 'badge bg-secondary px-2 py-1 fs-7']);
echo html_writer::end_div();

// Action Toolbar Top-Right
echo html_writer::start_div('d-flex align-items-center gap-2');
$addprofileurl = new moodle_url($url, ['action' => 'add_profile']);
echo html_writer::link($addprofileurl, '+ Add Custom Profile', ['class' => 'btn btn-sm btn-light text-dark font-weight-bold shadow-sm']);

$reseturl = new moodle_url($url, ['action' => 'reset_profiles', 'sesskey' => sesskey()]);
echo html_writer::link($reseturl, 'Reset Defaults', [
    'class' => 'btn btn-sm btn-outline-light opacity-75',
    'onclick' => 'return confirm("Reset all schedule profiles back to institutional defaults?");',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card-body p-4');
echo html_writer::div('Select an official school structure profile to instantly configure your institution\'s weekly timetabling slots, or click <strong>Edit Profile</strong> to customize period lengths and break windows:', 'text-muted mb-4');

echo html_writer::start_div('row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3');

foreach ($profiles as $pkey => $pinfo) {
    $theme = $pinfo['theme'] ?? 'primary';
    $icon  = $pinfo['icon'] ?? 'fa-graduation-cap';
    $name  = s($pinfo['name']);
    $badge = s($pinfo['badge'] ?? (($pinfo['period_minutes'] ?? 60) . '-Min Periods'));
    $desc  = s($pinfo['description'] ?? '');

    $start = s($pinfo['day_start'] ?? '08:00');
    $end   = s($pinfo['day_end'] ?? '17:00');
    $dayscount = count($pinfo['days'] ?? [1, 2, 3, 4, 5]);

    $tea   = (!empty($pinfo['tea_start']) && !empty($pinfo['tea_end'])) ? s($pinfo['tea_start']) . '-' . s($pinfo['tea_end']) : 'None';
    $lunch = (!empty($pinfo['lunch_start']) && !empty($pinfo['lunch_end'])) ? s($pinfo['lunch_start']) . '-' . s($pinfo['lunch_end']) : 'None';

    $applyurl = new moodle_url($url, ['action' => 'preset', 'profile' => $pkey, 'sesskey' => sesskey()]);
    $editprofurl = new moodle_url($url, ['action' => 'edit_profile', 'profile' => $pkey]);
    $delprofurl  = new moodle_url($url, ['action' => 'delete_profile', 'profile' => $pkey, 'sesskey' => sesskey()]);

    echo html_writer::start_div('col');
    echo html_writer::start_div('card h-100 att-profile-card shadow-sm border-0 bg-white position-relative d-flex flex-column');

    // Accent line on top
    echo html_writer::div('', 'card-header-accent att-accent-' . $theme);

    echo html_writer::start_div('card-body p-4 d-flex flex-column');

    // Card Header Info (Icon + Title + Badge)
    echo html_writer::start_div('d-flex align-items-start gap-3 mb-3');
    echo html_writer::div('<i class="fa ' . $icon . '"></i>', 'att-icon-box att-icon-' . $theme);
    echo html_writer::start_div();
    echo html_writer::tag('h5', $name, ['class' => 'card-title mb-1 font-weight-bold text-dark fs-6']);
    echo html_writer::tag('span', $badge, ['class' => 'badge bg-' . ($theme === 'purple' ? 'primary' : $theme) . ' text-white px-2 py-1 fs-7']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Card Description
    echo html_writer::tag('p', $desc, ['class' => 'card-text text-muted small mb-3 flex-grow-1']);

    // Specs Metadata Grid / Pills
    $dayslabel = profile_manager::format_days_label($pinfo['days'] ?? [1, 2, 3, 4, 5]);
    echo html_writer::start_div('d-flex flex-wrap gap-2 mb-3');
    echo html_writer::div('⏱️ <strong>' . $start . ' &ndash; ' . $end . '</strong>', 'att-meta-pill');
    echo html_writer::div('📅 <strong>' . $dayslabel . '</strong>', 'att-meta-pill');
    if ($tea !== 'None') {
        echo html_writer::div('☕ Tea: <strong>' . $tea . '</strong>', 'att-meta-pill');
    }
    if ($lunch !== 'None') {
        echo html_writer::div('🍽️ Lunch: <strong>' . $lunch . '</strong>', 'att-meta-pill');
    }
    echo html_writer::end_div();

    // Card Action Buttons Footer
    echo html_writer::start_div('d-flex align-items-center gap-2 pt-3 border-top mt-auto');

    // Apply Profile Button
    echo html_writer::link($applyurl, '<i class="fa fa-bolt me-1"></i> Apply Profile', [
        'class' => 'btn btn-' . ($theme === 'purple' ? 'primary' : $theme) . ' btn-sm flex-grow-1 font-weight-bold shadow-sm py-2',
        'onclick' => "return confirm('Apply profile \"{$name}\"? This will configure active slots.');",
    ]);

    // Edit Profile Button
    echo html_writer::link($editprofurl, '<i class="fa fa-pen me-1"></i> Edit Profile', [
        'class' => 'btn btn-outline-secondary btn-sm font-weight-bold px-3 py-2',
        'title' => 'Edit profile parameters and break windows',
    ]);

    // Delete custom profile button (if non-default)
    if (empty($pinfo['is_default'])) {
        echo html_writer::link($delprofurl, '<i class="fa fa-trash"></i>', [
            'class' => 'btn btn-outline-danger btn-sm px-2 py-2',
            'title' => 'Delete custom profile',
            'onclick' => "return confirm('Delete custom profile \"{$name}\"?');",
        ]);
    }

    echo html_writer::end_div(); // action row

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // col
}

echo html_writer::end_div(); // row
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// -------------------------------------------------------------------
// Component 2: Guided Bell Schedule Wizard
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white rounded-3');
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

echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::start_div('form-check d-flex align-items-center gap-2 mb-0');
echo html_writer::checkbox('wipe_existing', '1', true, ' Wipe existing time slots before applying', ['class' => 'form-check-input me-1', 'id' => 'chk_wipe_existing']);
echo html_writer::end_div();
echo html_writer::tag('button', 'Apply Bell Schedule', ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Component 3: Add / Edit Single Custom Slot
// -------------------------------------------------------------------
if ($editslot) {
    echo html_writer::start_div('card shadow-sm mb-4 border-primary rounded-3');
    echo html_writer::div(html_writer::tag('h5', 'Edit Time Slot Window', ['class' => 'mb-0 font-weight-bold text-primary']), 'card-header bg-light');
    echo html_writer::start_div('card-body p-4');

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
// Component 4: Visual Daily Bell Schedule Summary Table
// -------------------------------------------------------------------
$slots = $DB->get_records('local_schola_slots_slots', null, 'dayofweek ASC, starttime ASC');

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
    echo html_writer::div('No bell schedule time windows configured yet. Use the <strong>Guided Bell Schedule Wizard</strong> or select an <strong>Institutional Schedule Profile</strong> above to get started instantly.', 'alert alert-info shadow-sm p-4 text-center fs-6 rounded-3');
} else {
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    $table = new html_table();
    $table->head = ['ID', 'Day', 'Time Window', 'Functional Category', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle bg-white shadow-sm rounded-3'];

    foreach ($slots as $slot) {
        $dayname = $days[$slot->dayofweek] ?? ('Day ' . $slot->dayofweek);
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $slot->id]);
        $editbtn = html_writer::link($editurl, '<i class="fa fa-pen me-1"></i> Edit', ['class' => 'btn btn-sm btn-outline-primary font-weight-bold me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $slot->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, '<i class="fa fa-trash me-1"></i> Delete', [
            'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
            'onclick' => 'return confirm("Delete this time slot?");',
        ]);

        $typebadge = match ($slot->type) {
            'break' => '<span class="badge att-badge-break"><i class="fa fa-coffee me-1"></i> INSTITUTIONAL BREAK / BLOCKOUT</span>',
            'lab'   => '<span class="badge att-badge-lab"><i class="fa fa-flask me-1"></i> LABORATORY PRACTICAL</span>',
            'exam'  => '<span class="badge att-badge-exam"><i class="fa fa-file-alt me-1"></i> EXAMINATION PERIOD</span>',
            default => '<span class="badge att-badge-class"><i class="fa fa-book me-1"></i> CLASS LECTURE</span>',
        };

        $table->data[] = [
            '<span class="font-weight-bold text-dark">#' . $slot->id . '</span>',
            '<strong class="text-dark fs-6">' . $dayname . '</strong>',
            '<span class="badge bg-light text-dark border px-3 py-2 fs-6 font-weight-bold"><i class="fa fa-clock me-1 text-primary"></i> ' . s($slot->starttime) . ' &mdash; ' . s($slot->endtime) . '</span>',
            $typebadge,
            $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
