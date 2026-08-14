use std::collections::HashSet;
use std::time::Instant;
use crate::models::{RoomInput, SolveRequest, SolveResponse, ScheduleAssignment};

pub struct CloudSolver;

impl CloudSolver {
    pub fn solve(req: SolveRequest) -> SolveResponse {
        let start = Instant::now();

        if req.courses.is_empty() || req.rooms.is_empty() || req.slots.is_empty() {
            return SolveResponse {
                status: "error".to_string(),
                execution_time_ms: start.elapsed().as_millis(),
                total_courses: req.courses.len(),
                assigned_count: 0,
                fitness_score: 0.0,
                assignments: vec![],
                unassigned_courses: req.courses.iter().map(|c| c.id).collect(),
                message: "Insufficient input: courses, rooms, and slots must be non-empty.".to_string(),
            };
        }

        let mut assignments: Vec<ScheduleAssignment> = Vec::new();
        let mut unassigned: Vec<usize> = Vec::new();

        // Matrix tracking occupied (room_id, slot_id)
        let mut occupied_room_slots: HashSet<(usize, usize)> = HashSet::new();

        // Matrix tracking occupied (lecturer_id, slot_id)
        let mut occupied_lecturer_slots: HashSet<(usize, usize)> = HashSet::new();

        // Separate lab rooms vs standard rooms
        let lab_rooms: Vec<&RoomInput> = req.rooms.iter().filter(|r| r.is_lab).collect();
        let all_rooms: Vec<&RoomInput> = req.rooms.iter().collect();

        let mut total_assigned = 0;

        for course in &req.courses {
            let mut course_assigned = false;
            let candidate_rooms = if course.is_lab_required && !lab_rooms.is_empty() {
                &lab_rooms
            } else {
                &all_rooms
            };

            'slot_search: for slot in &req.slots {
                if course.lecturer_id > 0 && occupied_lecturer_slots.contains(&(course.lecturer_id, slot.id)) {
                    continue 'slot_search;
                }

                for room in candidate_rooms {
                    if !occupied_room_slots.contains(&(room.id, slot.id)) {
                        // Successfully assigned!
                        occupied_room_slots.insert((room.id, slot.id));
                        if course.lecturer_id > 0 {
                            occupied_lecturer_slots.insert((course.lecturer_id, slot.id));
                        }

                        assignments.push(ScheduleAssignment {
                            course_id: course.id,
                            room_id: room.id,
                            slot_id: slot.id,
                            session_number: 1,
                        });

                        total_assigned += 1;
                        course_assigned = true;
                        break 'slot_search;
                    }
                }
            }

            if !course_assigned {
                unassigned.push(course.id);
            }
        }

        let elapsed = start.elapsed().as_millis();
        let total_courses = req.courses.len();
        let fitness = if total_courses > 0 {
            (total_assigned as f64 / total_courses as f64) * 100.0
        } else {
            0.0
        };

        let status = if unassigned.is_empty() {
            "success".to_string()
        } else {
            "partial".to_string()
        };

        let message = format!(
            "Rust Cloud Solver processed {} courses in {}ms. Solved {}/{} (Fitness: {:.1}%).",
            total_courses, elapsed, total_assigned, total_courses, fitness
        );

        SolveResponse {
            status,
            execution_time_ms: elapsed,
            total_courses,
            assigned_count: total_assigned,
            fitness_score: fitness,
            assignments,
            unassigned_courses: unassigned,
            message,
        }
    }
}
