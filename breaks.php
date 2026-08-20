<?php
// local/schola_slots/breaks.php
// Institutional Breaks Masterlist Studio for Schola Slots

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();
require_login();
require_capability('local/schola_slots:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/schola_slots/breaks.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Institutional Breaks Masterlist');
$PAGE->set_heading('Institutional Breaks Masterlist');

// -------------------------------------------------------------------
// Ensure Table Schema Support (name field on local_schola_slots_slots)
// -------------------------------------------------------------------
$dbman = $DB->get_manager();
$table = new xmldb_table('local_schola_slots_slots');
$field = new xmldb_field('name', XMLDB_TYPE_CHAR, '100', null, false, false, '');
if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
}

// -------------------------------------------------------------------
// Handle Actions: Add, Edit, Delete
// -------------------------------------------------------------------
if ($action === 'add' && data_submitted() && confirm_sesskey()) {
    $title       = required_param('title', PARAM_TEXT);
    $daywindow   = optional_param('daywindow', 0, PARAM_INT); // 0 = All Days, 1 = Mon ... 7 = Sun
    $starttime   = required_param('starttime', PARAM_RAW);
    $endtime     = required_param('endtime', PARAM_RAW);
    $scheduletype = optional_param('scheduletype', 'class', PARAM_ALPHA);

    $record = (object)[
        'type'      => 'break',
        'name'      => s($title),
        'dayofweek' => $daywindow,
        'starttime' => s($starttime),
        'endtime'   => s($endtime),
    ];
    $DB->insert_record('local_schola_slots_slots', $record);
    redirect($url, 'Institutional break window added successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'edit' && data_submitted() && confirm_sesskey()) {
    $id          = required_param('id', PARAM_INT);
    $title       = required_param('title', PARAM_TEXT);
    $daywindow   = optional_param('daywindow', 0, PARAM_INT);
    $starttime   = required_param('starttime', PARAM_RAW);
    $endtime     = required_param('endtime', PARAM_RAW);

    $record = (object)[
        'id'        => $id,
        'type'      => 'break',
        'name'      => s($title),
        'dayofweek' => $daywindow,
        'starttime' => s($starttime),
        'endtime'   => s($endtime),
    ];
    $DB->update_record('local_schola_slots_slots', $record);
    redirect($url, 'Institutional break updated successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_slots_slots', ['id' => $id, 'type' => 'break']);
    redirect($url, 'Institutional break removed.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Render Unified Top Executive Navigation Bar
echo local_schola_slots\output\renderer::render_nav_header('breaks');

// Days Mapping
$daynames = [
    0 => 'All Days',
    1 => 'Monday Only',
    2 => 'Tuesday Only',
    3 => 'Wednesday Only',
    4 => 'Thursday Only',
    5 => 'Friday Only',
    6 => 'Saturday Only',
    7 => 'Sunday Only',
];

// Fetch all break slots
$breaks = $DB->get_records('local_schola_slots_slots', ['type' => 'break'], 'starttime ASC');
if (empty($breaks)) {
    $DB->insert_record('local_schola_slots_slots', (object)[
        'type'      => 'break',
        'name'      => 'Tea Break',
        'dayofweek' => 0,
        'starttime' => '08:00',
        'endtime'   => '09:00',
    ]);
    $DB->insert_record('local_schola_slots_slots', (object)[
        'type'      => 'break',
        'name'      => 'Lunch Break',
        'dayofweek' => 0,
        'starttime' => '13:00',
        'endtime'   => '14:00',
    ]);
    $breaks = $DB->get_records('local_schola_slots_slots', ['type' => 'break'], 'starttime ASC');
}

// -------------------------------------------------------------------
// Executive Header Studio Banner
// -------------------------------------------------------------------
?>
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 font-monospace small rounded-pill mb-2">INSTITUTIONAL RESOURCE HUB</span>
            <h4 class="fw-bold text-dark mb-1">Institutional Master Data Studio</h4>
            <p class="text-muted small mb-0">Manage course catalogs, campus venues, bell time slots, and faculty members for cloud schedule generation.</p>
        </div>
    </div>
</div>

<!-- Main Institutional Breaks Masterlist Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-3 border-bottom gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-1">Institutional Breaks Masterlist</h5>
            <p class="text-muted small mb-0">Configure devotion windows, lunch breaks, chapel services, and assemblies spanning all venue rows.</p>
        </div>
        <div>
            <button type="button" class="btn btn-emerald rounded-pill font-weight-bold px-4 py-2 d-inline-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#addBreakModal">
                <i class="fa fa-plus me-2"></i>Add Institutional Break
            </button>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-light p-2.5 rounded-3 mb-3 gap-2">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 450px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
                <input type="text" id="breakSearchInput" class="form-control border-start-0" placeholder="Search break title or day..." onkeyup="filterBreaks()">
            </div>
            <select class="form-select form-select-sm" style="max-width: 170px;">
                <option value="all">ALL Schedules</option>
                <option value="class">Class Schedules</option>
                <option value="exam">Exam Schedules</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted small">
            <span>Showing <strong><?php echo count($breaks); ?></strong> items</span>
            <select class="form-select form-select-sm" style="width: auto;">
                <option>15 per page</option>
                <option>25 per page</option>
                <option>50 per page</option>
            </select>
        </div>
    </div>

    <!-- Breaks Table Grid -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="breaksMasterTable">
            <thead>
                <tr class="text-uppercase font-monospace text-muted small border-bottom bg-light">
                    <th style="width: 40px;" class="py-3 px-3"><input type="checkbox" class="form-check-input"></th>
                    <th style="width: 50px;" class="py-3">#</th>
                    <th class="py-3">BREAK TITLE</th>
                    <th class="py-3">DAY WINDOW</th>
                    <th class="py-3">TIME SLOT</th>
                    <th class="py-3">SCHEDULE</th>
                    <th class="py-3 text-end px-3">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($breaks)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa fa-coffee fa-2x mb-2 d-block text-secondary"></i>
                            No institutional break windows configured yet. Click <strong>+ Add Institutional Break</strong> above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = 1; foreach ($breaks as $b): ?>
                        <?php
                            $title = !empty($b->name) ? s($b->name) : (($b->starttime >= '11:30') ? 'Lunch Break' : 'Tea Break');
                            $daylabel = $daynames[$b->dayofweek] ?? 'All Days';
                            $timeslot = s($b->starttime) . ' &mdash; ' . s($b->endtime);
                        ?>
                        <tr class="break-table-row">
                            <td class="px-3"><input type="checkbox" class="form-check-input"></td>
                            <td class="font-monospace text-muted small"><?php echo $idx++; ?></td>
                            <td>
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1.5 font-weight-bold" style="font-size: 12px; background-color: #fff7ed !important; color: #c2410c !important; border-color: #ffedd5 !important;">
                                    ☕ <?php echo $title; ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark font-monospace small"><?php echo $daylabel; ?></td>
                            <td class="font-monospace text-muted small"><?php echo $timeslot; ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 font-monospace extra-small rounded-pill" style="background-color: #ecfdf5 !important; color: #047857 !important; border-color: #a7f3d0 !important;">
                                    CLASS
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 me-1 extra-small"
                                        data-bs-toggle="modal" data-bs-target="#editBreakModal<?php echo $b->id; ?>">
                                    Edit
                                </button>
                                <a href="<?php echo new moodle_url('/local/schola_slots/breaks.php', ['action' => 'delete', 'id' => $b->id, 'sesskey' => sesskey()]); ?>"
                                   class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 extra-small"
                                   onclick="return confirm('Are you sure you want to delete this break window?');">
                                    Delete
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Break Modal for each record -->
                        <div class="modal fade" id="editBreakModal<?php echo $b->id; ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow-lg">
                              <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark">Edit Institutional Break</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <form method="post" action="breaks.php">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $b->id; ?>">
                                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                                <div class="modal-body py-3">
                                  <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Break Title (e.g. Devotion, Lunch Break, Chapel)</label>
                                    <input type="text" name="title" value="<?php echo s($title); ?>" class="form-control rounded-3" required>
                                  </div>
                                  <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Day Window</label>
                                    <select name="daywindow" class="form-select rounded-3">
                                      <?php foreach ($daynames as $dval => $dname): ?>
                                        <option value="<?php echo $dval; ?>" <?php echo ($b->dayofweek == $dval) ? 'selected' : ''; ?>><?php echo $dname; ?></option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div class="row g-2 mb-3">
                                    <div class="col-6">
                                      <label class="form-label small fw-bold text-muted">Start Time</label>
                                      <input type="time" name="starttime" value="<?php echo s($b->starttime); ?>" class="form-control rounded-3" required>
                                    </div>
                                    <div class="col-6">
                                      <label class="form-label small fw-bold text-muted">End Time</label>
                                      <input type="time" name="endtime" value="<?php echo s($b->endtime); ?>" class="form-control rounded-3" required>
                                    </div>
                                  </div>
                                </div>
                                <div class="modal-footer border-top-0 pt-0">
                                  <button type="button" class="btn btn-outline-secondary rounded-pill font-weight-bold px-4" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" class="btn btn-emerald rounded-pill font-weight-bold px-4">Save Changes</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: + Add Institutional Break -->
<div class="modal fade" id="addBreakModal" tabindex="-1" aria-labelledby="addBreakModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="addBreakModalLabel">+ Add Institutional Break</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="breaks.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <div class="modal-body py-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Break Title (e.g. Devotion, Lunch Break, Chapel)</label>
            <input type="text" name="title" class="form-control rounded-3" placeholder="Devotion" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Day Window</label>
            <select name="daywindow" class="form-select rounded-3">
              <option value="0">All Days</option>
              <option value="1">Monday Only</option>
              <option value="2">Tuesday Only</option>
              <option value="3">Wednesday Only</option>
              <option value="4">Thursday Only</option>
              <option value="5">Friday Only</option>
              <option value="6">Saturday Only</option>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Start Time</label>
              <input type="time" name="starttime" value="08:00" class="form-control rounded-3" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">End Time</label>
              <input type="time" name="endtime" value="09:00" class="form-control rounded-3" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Applicable Schedule</label>
            <select name="scheduletype" class="form-select rounded-3">
              <option value="class">All Timetables</option>
              <option value="class">Class Schedules Only</option>
              <option value="exam">Exam Schedules Only</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill font-weight-bold px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-emerald rounded-pill font-weight-bold px-4">Add Break</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterBreaks() {
    var query = document.getElementById("breakSearchInput").value.toLowerCase().trim();
    var rows = document.querySelectorAll("#breaksMasterTable tbody tr.break-table-row");
    rows.forEach(function(row) {
        var text = row.innerText.toLowerCase();
        if (!query || text.indexOf(query) !== -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>

<?php
echo $OUTPUT->footer();
