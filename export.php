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
 * Export and printable view script for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
    require_login();
    $context = context_system::instance();
    require_capability('local/academic_timetabler:manage', $context);
}

$action = optional_param('action', 'print', PARAM_ALPHA);
$roomid = optional_param('roomid', 0, PARAM_INT);
$teacherid = optional_param('teacherid', 0, PARAM_INT);

global $DB;

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];

// -------------------------------------------------------------------
// Action: CSV Export
// -------------------------------------------------------------------
if ($action === 'csv') {
    $where = [];
    $params = [];
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
              FROM {local_att_schedules} s
              JOIN {course} c ON c.id = s.courseid
              JOIN {local_att_rooms} r ON r.id = s.roomid
              JOIN {local_att_slots} sl ON sl.id = s.slotid
              LEFT JOIN {user} u ON u.id = s.teacherid
              {$wherestr}
          ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

    $schedules = $DB->get_records_sql($sql, $params);

    $filename = 'academic_timetable_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Schedule ID',
        'Course Code',
        'Course Full Name',
        'Room / Venue',
        'Day of Week',
        'Start Time',
        'End Time',
        'Instructor',
        'Schedule Type'
    ]);

    foreach ($schedules as $s) {
        $dayname = $days[$s->dayofweek] ?? 'Day ' . $s->dayofweek;
        $teacher = (!empty($s->firstname) || !empty($s->lastname)) ? fullname($s) : 'Unassigned';
        fputcsv($output, [
            $s->id,
            $s->coursecode,
            $s->coursename,
            $s->roomname,
            $dayname,
            $s->starttime,
            $s->endtime,
            $teacher,
            strtoupper($s->schedule_type),
        ]);
    }
    fclose($output);
    exit(0);
}

// -------------------------------------------------------------------
// Action: Print / PDF View Page
// -------------------------------------------------------------------
$PAGE->set_url(new moodle_url('/local/academic_timetabler/export.php', ['action' => 'print']));
$PAGE->set_context($context);
$PAGE->set_title('Printable Academic Timetable');
$PAGE->set_pagelayout('embedded'); // Clean embedded layout without site headers/footers

echo $OUTPUT->header();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; background: #fff; margin: 20px; }
        .print-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
        .print-title { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0; }
        .print-meta { font-size: 13px; color: #64748b; text-align: right; }
        .table-printable { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .table-printable th { background-color: #f1f5f9; color: #334155; text-align: left; padding: 10px; border: 1px solid #cbd5e1; font-weight: 600; }
        .table-printable td { padding: 9px 10px; border: 1px solid #cbd5e1; }
        .table-printable tr:nth-child(even) { background-color: #f8fafc; }
        .badge-type { display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; background: #e2e8f0; color: #334155; }
        .no-print { margin-bottom: 20px; display: flex; gap: 10px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print();" class="btn btn-primary font-weight-bold">
        🖨️ Print / Save as PDF
    </button>
    <a href="<?php echo (new moodle_url('/local/academic_timetabler/export.php', ['action' => 'csv']))->out(false); ?>" class="btn btn-success">
        📥 Export to CSV
    </a>
    <button onclick="window.close();" class="btn btn-outline-secondary">
        Close Window
    </button>
</div>

<div class="print-header">
    <div>
        <h1 class="print-title">Institutional Master Academic Timetable</h1>
        <span style="font-size: 14px; color: #475569;">Official Published Course Schedule & Venue Allocations</span>
    </div>
    <div class="print-meta">
        <div><strong>Generated:</strong> <?php echo userdate(time(), '%B %d, %Y - %H:%M'); ?></div>
        <div><strong>Institution:</strong> <?php echo s($SITE->fullname); ?></div>
    </div>
</div>

<?php
$where = [];
$params = [];
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
          FROM {local_att_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_att_rooms} r ON r.id = s.roomid
          JOIN {local_att_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
          {$wherestr}
      ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

$schedules = $DB->get_records_sql($sql, $params);

if (empty($schedules)) {
    echo '<div class="alert alert-info">No schedule entries found for export.</div>';
} else {
    echo '<table class="table-printable">';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Course Code & Title</th><th>Assigned Venue</th><th>Day & Time Window</th><th>Instructor / Lecturer</th><th>Type</th>';
    echo '</tr></thead><tbody>';

    foreach ($schedules as $s) {
        $dayname = $days[$s->dayofweek] ?? 'Day ' . $s->dayofweek;
        $teacher = (!empty($s->firstname) || !empty($s->lastname)) ? fullname($s) : 'Unassigned';
        echo '<tr>';
        echo '<td>' . $s->id . '</td>';
        echo '<td><strong>' . s($s->coursecode) . '</strong><br><small style="color:#64748b;">' . s($s->coursename) . '</small></td>';
        echo '<td>' . s($s->roomname) . '</td>';
        echo '<td><strong>' . $dayname . '</strong> (' . s($s->starttime) . ' - ' . s($s->endtime) . ')</td>';
        echo '<td>' . s($teacher) . '</td>';
        echo '<td><span class="badge-type">' . strtoupper($s->schedule_type) . '</span></td>';
        echo '</tr>';
    }
    echo 'tbody></table>';
}
?>

<script>
    // Auto trigger print dialog if requested
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
        window.addEventListener('load', () => window.print());
    }
</script>

</body>
</html>
<?php
echo $OUTPUT->footer();
