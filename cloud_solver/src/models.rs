use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CourseInput {
    pub id: usize,
    pub code: String,
    pub name: String,
    #[serde(default = "default_sessions")]
    pub weekly_sessions: usize,
    #[serde(default)]
    pub is_lab_required: bool,
    #[serde(default)]
    pub lecturer_id: usize,
}

fn default_sessions() -> usize {
    1
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct RoomInput {
    pub id: usize,
    pub name: String,
    pub capacity: usize,
    #[serde(default)]
    pub is_lab: bool,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SlotInput {
    pub id: usize,
    pub day_of_week: usize, // 1 = Mon, 5 = Fri, 6 = Sat
    pub start_time: String,
    pub end_time: String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SolveOptions {
    #[serde(default = "default_strategy")]
    pub day_distribution: String,
    #[serde(default = "default_iterations")]
    pub max_iterations: usize,
}

fn default_strategy() -> String {
    "balanced".to_string()
}

fn default_iterations() -> usize {
    100_000
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SolveRequest {
    pub courses: Vec<CourseInput>,
    pub rooms: Vec<RoomInput>,
    pub slots: Vec<SlotInput>,
    #[serde(default)]
    pub options: Option<SolveOptions>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ScheduleAssignment {
    pub course_id: usize,
    pub room_id: usize,
    pub slot_id: usize,
    pub session_number: usize,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SolveResponse {
    pub status: String,
    pub execution_time_ms: u128,
    pub total_courses: usize,
    pub assigned_count: usize,
    pub fitness_score: f64,
    pub assignments: Vec<ScheduleAssignment>,
    pub unassigned_courses: Vec<usize>,
    pub message: String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct HealthResponse {
    pub status: String,
    pub service: String,
    pub version: String,
    pub engine: String,
}
