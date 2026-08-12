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

namespace local_academic_timetabler\algorithm;

use local_academic_timetabler\licensing\license_manager;

/**
 * Constraint solver algorithm for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class solver {
    /** @var array Array of course objects. */
    private array $courses = [];

    /** @var array Array of quiz objects. */
    private array $quizzes = [];

    /** @var array Array of available time slot records. */
    private array $slots = [];

    /** @var array Array of campus room records. */
    private array $rooms = [];

    /** @var array Solution mapping output. */
    private array $solution = [];

    /**
     * Constructor for solver.
     *
     * @param array $slots Available slots.
     * @param array $rooms Available rooms.
     */
    public function __construct(array $slots, array $rooms) {
        $this->slots = $slots;
        $this->rooms = $rooms;
    }

    /**
     * Load course and enrollment metadata.
     *
     * @param array $courses Courses list.
     * @return void
     * @throws \moodle_exception If license tier course limits are exceeded.
     */
    public function load_courses(array $courses): void {
        $coursecount = count($courses);
        if (!license_manager::can_solve_courses($coursecount)) {
            $limit = license_manager::COMMUNITY_COURSE_LIMIT;
            throw new \moodle_exception(
                'license_err_limit',
                'local_academic_timetabler',
                '',
                [$coursecount, $limit]
            );
        }

        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'moodle/course:view', 0, 'u.id');
            $teachers = get_enrolled_users($context, 'moodle/course:update', 0, 'u.id');

            $teacherid = !empty($teachers) ? reset($teachers)->id : 0;
            $this->courses[$course->id] = (object)[
                'id' => $course->id,
                'students' => array_keys($students),
                'teacher_id' => $teacherid,
            ];
        }
    }

    /**
     * Solve schedule for all loaded courses.
     *
     * @return bool True if solution found, false otherwise.
     */
    public function solve_all(): bool {
        // Most-Constrained-First ordering by enrolled count.
        usort($this->courses, fn($a, $b) => count($b->students) <=> count($a->students));
        return $this->backtrack_classes(0);
    }

    /**
     * Recursive backtracking to assign rooms and slots to courses.
     *
     * @param int $index Current course index in array.
     * @return bool True if successful assignment sequence completed.
     */
    private function backtrack_classes(int $index): bool {
        if ($index >= count($this->courses)) {
            return true;
        }

        $course = array_values($this->courses)[$index];
        $classslots = array_filter($this->slots, fn($s) => $s->type === 'class');

        foreach ($classslots as $slot) {
            foreach ($this->rooms as $room) {
                if ($this->is_valid_class_assignment($course, $slot, $room)) {
                    $this->solution['classes'][$course->id] = [
                        'slot_id' => $slot->id,
                        'room_id' => $room->id,
                        'teacher_id' => $course->teacher_id,
                    ];

                    if ($this->backtrack_classes($index + 1)) {
                        return true;
                    }

                    unset($this->solution['classes'][$course->id]);
                }
            }
        }

        return false;
    }

    /**
     * Verify if candidate class assignment satisfies hard constraints.
     *
     * @param object $course Course object.
     * @param object $slot Time slot object.
     * @param object $room Room object.
     * @return bool True if assignment is valid.
     */
    private function is_valid_class_assignment($course, $slot, $room): bool {
        if ($room->capacity < count($course->students)) {
            return false;
        }

        foreach ($this->solution['classes'] ?? [] as $assignedcourseid => $assignment) {
            if ($assignment['slot_id'] == $slot->id) {
                if ($assignment['room_id'] == $room->id) {
                    return false;
                }
                if ($assignment['teacher_id'] == $course->teacher_id) {
                    return false;
                }
                $assignedcourse = $this->courses[$assignedcourseid];
                $sharedstudents = array_intersect($course->students, $assignedcourse->students);
                if (!empty($sharedstudents)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Retrieve calculated schedule solution.
     *
     * @return array Solution data structure.
     */
    public function get_solution(): array {
        return $this->solution;
    }
}
