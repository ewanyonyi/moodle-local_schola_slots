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
 * English language strings for local_schola_slots.
 *
 * @package     local_schola_slots
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['add_room'] = 'Add New Room';
$string['add_single_room'] = 'Add Single Campus Room';
$string['add_slot'] = 'Add Time Slot';
$string['all_campus_venues'] = '-- All Campus Venues --';
$string['all_departments'] = '-- Entire Institution (All Departments) --';
$string['all_departments_filter'] = '-- All Departments --';
$string['all_faculty_members'] = '-- All Faculty Members --';
$string['append_existing_mode'] = 'Append Mode (Preserve Existing Timetables & Schedule Around Them)';
$string['batch_csv_import'] = 'Batch CSV Room Import';
$string['buy_enterprise_license'] = 'Get Pro Cloud License';
$string['capacity'] = 'Capacity';
$string['class_schedules'] = 'Class Schedules';
$string['clear_timetables'] = 'Clear Timetables';
$string['cloud_offline_limit_err'] = 'Schola Slots Cloud Solver Acceleration Engine is currently unreachable. Large institutional datasets (> 50 courses) require active Cloud Engine connectivity. Please check your cloud server connection at scholaslots.com or contact support@scholaslots.com.';
$string['color_theme'] = 'Color Theme';
$string['configured_campus_venues'] = 'Configured Campus Venues';
$string['confirm_clear_timetables'] = 'Are you sure you want to delete selected generated schedule entries?';
$string['conflict_prevention_notice'] = 'Cross-schedule conflict prevention will automatically protect active venue and instructor bookings.';
$string['continue_community_edition'] = 'Continue with Native Engine';
$string['csp_generator_heading'] = 'Automated CSP Solver & Timetable Generator';
$string['day_of_week'] = 'Day of Week';
$string['department_scope'] = 'Department / Course Category Scope';
$string['edit_campus_room'] = 'Edit Campus Room / Venue';
$string['end_time'] = 'End Time';
$string['exam_schedules'] = 'Exam Schedules';
$string['examination_schedule'] = 'Examination Schedule';
$string['export_csv'] = 'Export to CSV';
$string['generate_timetable'] = 'Generate Timetable';
$string['generation_conflict_mode'] = 'Generation & Conflict Mode';
$string['import_rooms_csv'] = 'Import Rooms from CSV';
$string['is_lab'] = 'Laboratory / Specialized Venue';
$string['license_community_notice'] = 'Starter Edition Active (Free Open Source — Up to 50 active courses & 25 campus rooms). Upgrade to Pro Cloud Engine ($499/yr) for unlimited capacity.';
$string['license_enterprise_active'] = 'Pro Cloud Engine Active (UNLIMITED active courses, campus rooms & off-server high-speed solver API)';
$string['license_err_limit'] = 'Starter Edition course limit exceeded (%1$d / %2$d). Upgrade to Pro Cloud Engine for unlimited courses.';
$string['license_key'] = 'Pro Cloud License Key';
$string['license_key_desc'] = 'Enter your commercial LemonSqueezy license key (e.g. ATT-PRO-XXXX-XXXX-XXXX) to unlock Pro Cloud Engine capabilities.';
$string['license_status'] = 'License Tier Status';
$string['manage_rooms'] = 'Manage Venues & Rooms';
$string['manage_schedules'] = 'View Generated Timetables';
$string['manage_slots'] = 'Manage Time Slots';
$string['manage_timetabler'] = 'Manage Schola Slots';
$string['master_list'] = 'Master List';
$string['nav_overview'] = 'Overview';
$string['nav_rooms'] = 'Manage Rooms';
$string['nav_schedules'] = 'View Timetables';
$string['nav_slots'] = 'Bell Schedule & Slots';
$string['no_rooms'] = 'No rooms configured yet. Add campus venues below.';
$string['no_schedules'] = 'No timetables generated yet. Run the solver engine from the dashboard.';
$string['no_slots'] = 'No time slots configured yet. Add timetable periods below.';
$string['overwrite_existing_mode'] = 'Overwrite Existing Timetables of Selected Type';
$string['pluginname'] = 'Schola Slots';
$string['print_pdf'] = 'Print / Save to PDF';
$string['privacy:metadata'] = 'The Schola Slots plugin stores schedule assignment records linking courses, rooms, slots, and instructors, and optionally interfaces with external solver and licensing APIs.';
$string['privacy:metadata:lemonsqueezy'] = 'Communicates with LemonSqueezy API to validate commercial license key status.';
$string['privacy:metadata:lemonsqueezy:license_key'] = 'The commercial license key configured by site administrators.';
$string['privacy:metadata:schedules'] = 'Stores generated timetable assignments linking courses, rooms, time slots, and assigned instructors.';
$string['privacy:metadata:schedules:courseid'] = 'The ID of the course allocated in the timetable.';
$string['privacy:metadata:schedules:quizid'] = 'The ID of the examination quiz (if applicable).';
$string['privacy:metadata:schedules:roomid'] = 'The ID of the campus venue assigned.';
$string['privacy:metadata:schedules:slotid'] = 'The ID of the time slot assigned.';
$string['privacy:metadata:schedules:teacherid'] = 'The ID of the assigned lecturer or instructor.';
$string['privacy:metadata:solver_service'] = 'Communicates with Schola Slots Cloud Acceleration Solver service at scholaslots.com to compute high-performance conflict-free schedules.';
$string['privacy:metadata:solver_service:courseid'] = 'The ID of the course being scheduled.';
$string['privacy:metadata:solver_service:lecturer_id'] = 'The ID of the instructor assigned to the course.';
$string['profile_all_timetables'] = 'Profile: All Timetables';
$string['profile_class_only'] = 'Profile: Class Timetables Only';
$string['profile_exam_only'] = 'Profile: Exam Timetables Only';
$string['regular_class_schedule'] = 'Regular Semester Class Schedule';
$string['reset_filters'] = 'Reset Filters';
$string['room_name'] = 'Room Name';
$string['run_solver_button'] = 'Run Solver & Generate Timetable';
$string['sample_csv'] = 'Sample CSV';
$string['save_room'] = 'Save Room';
$string['schola_slots:manage'] = 'Manage Schola Slots plugin settings and timetables';
$string['select_csv_file'] = 'Select CSV / Text File';
$string['start_time'] = 'Start Time';
$string['strategy_4day'] = 'Strategy: 4-Day Compact (Mon-Thu)';
$string['strategy_6day'] = 'Strategy: 6-Day Schedule (Mon-Sat)';
$string['strategy_balanced'] = 'Strategy: Balanced 5-Day (Mon-Fri)';
$string['strategy_frontload'] = 'Strategy: Sequential Frontload';
$string['theme_dark'] = 'Night Dark';
$string['theme_info'] = 'Teal / Cyan';
$string['theme_primary'] = 'Primary Blue';
$string['theme_purple'] = 'Modular Purple';
$string['theme_success'] = 'Emerald Green';
$string['theme_warning'] = 'Amber / Gold';
$string['timetable_profile_type'] = 'Timetable Profile / Type';
$string['total_allocations'] = 'Total Allocations';
$string['update_room'] = 'Update Room';
$string['weekly_matrix'] = 'Weekly Matrix';
