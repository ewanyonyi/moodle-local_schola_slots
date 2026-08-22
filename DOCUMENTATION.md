# Schola Slots Moodle Plugin — Official Documentation

Welcome to the official documentation for **Schola Slots** (`local_schola_slots`), an enterprise-grade timetabling and constraint-satisfaction solver plugin for Moodle LMS.

---

## 📌 Table of Contents

1. [Overview & Architecture](#overview--architecture)
2. [Key Features](#key-features)
3. [Installation & Requirements](#installation--requirements)
4. [Step-by-Step Administrative Workflow](#step-by-step-administrative-workflow)
   - [Step 1: Campus Venues & Room Setup](#step-1-campus-venues--room-setup)
   - [Step 2: Bell Schedule Profiles & Time Slots](#step-2-bell-schedule-profiles--time-slots)
   - [Step 3: Breaks & Blockout Windows](#step-3-breaks--blockout-windows)
   - [Step 4: Generating Conflict-Free Timetables](#step-4-generating-conflict-free-timetables)
   - [Step 5: View, Filter, Export & Print Studio](#step-5-view-filter-export--print-studio)
5. [CSV Batch Import Specifications](#csv-batch-import-specifications)
6. [Pro Rust Cloud Acceleration Engine](#pro-rust-cloud-acceleration-engine)
7. [CLI Data Generator](#cli-data-generator)
8. [Support & Contact](#support--contact)

---

## 🏛️ Overview & Architecture

Schola Slots solves the NP-hard Course Timetabling Problem (CTP) directly within Moodle. It operates using dual constraint engines:

* **Native PHP Constraint Solver**: Built-in algorithm running directly on your Moodle web server under GPLv3.
* **Pro Rust Cloud Microservice**: High-speed, off-server constraint satisfaction solver capable of solving multi-thousand course matrices in under 50 milliseconds.

---

## ✨ Key Features

- **Dual Timetable Profiles**: Separate generation workflows for **Weekly Class Schedules** (recurring lectures/labs) and **Examination Windows** (midterm/final assessment blocks).
- **Versioned Schedules**: Save multiple timetable options (e.g. *Semester I 2026*, *Draft Option B*) side-by-side without overwriting existing data.
- **Append & Overwrite Modes**: Protect existing active schedules while generating new departmental matrices.
- **Automatic Venue Classification**: Auto-detection for virtual spaces (*Online*, *Zoom*, *Teams*) and laboratory-specific equipment constraints.
- **CSV Batch Import Wizard**: Bulk-upload hundreds of rooms and time slots in seconds.

---

## ⚙️ Installation & Requirements

### System Requirements
* **Moodle Version**: Moodle 4.1 or higher (including Moodle 4.4+ and 5.x)
* **PHP**: PHP 8.1+ with `json` and `curl` extensions
* **Database**: MariaDB 10.6+, MySQL 8.0+, or PostgreSQL 13+

### Manual Plugin Installation
1. Download `local_schola_slots_v1.0.8.zip` from the release downloads.
2. Log into your Moodle site as an Administrator.
3. Navigate to **Site Administration ➔ Plugins ➔ Install plugins**.
4. Upload the ZIP file and click **Install plugin from the ZIP file**.
5. Complete the database schema upgrade.

---

## 🛠️ Step-by-Step Administrative Workflow

### Step 1: Campus Venues & Room Setup
Navigate to **Schola Slots ➔ Rooms**.
- Add lecture halls, auditoriums, classrooms, laboratories, and virtual spaces.
- Specify **Seating Capacity** to ensure class enrollments do not exceed room limits.
- Toggle **Laboratory / Computer Studio** for courses requiring specialized software or equipment.
- Virtual spaces containing "Online", "Virtual", or "Zoom" are automatically flagged for remote delivery.

### Step 2: Bell Schedule Profiles & Time Slots
Navigate to **Schola Slots ➔ Profiles & Slots**.
- Apply institutional presets (e.g., *University Standard*, *3-Hour Block*, *High School 45-min*).
- Custom slots can be added individually or batch-imported via CSV.

### Step 3: Breaks & Blockout Windows
Navigate to **Schola Slots ➔ Breaks**.
- Define campus-wide morning tea breaks and lunch break windows.
- The solver automatically treats break windows as occupied blockouts to prevent scheduling conflicts during lunch or campus events.

### Step 4: Generating Conflict-Free Timetables
Navigate to **Schola Slots ➔ Timetables** and click **Generate Timetable**.
- **Timetable Title**: Give your schedule a descriptive name (e.g., *Semester I 2026 Matrix*).
- **Timetable Type**: Select **Class Schedule** or **Exam Schedule**.
- **Scope**: Choose an individual Department/Category or schedule the entire institution.
- **Conflict Mode**: Choose between *Version Mode*, *Overwrite Mode*, or *Append Mode*.

### Step 5: View, Filter, Export & Print Studio
Navigate to **Schola Slots ➔ Timetables**.
- Filter matrix grids by Department, Room, or Instructor.
- Export schedule assignments to CSV.
- Generate printer-friendly PDF timetables for campus notice boards.

---

## 📄 CSV Batch Import Specifications

### 1. Rooms CSV Format (`rooms.php`)
```csv
Name, Capacity, Is Lab
Main Auditorium, 300, 0
Computer Lab 1, 40, 1
Virtual Zoom Room A, 500, 0
```

### 2. Time Slots CSV Format (`slots.php`)
```csv
dayofweek, starttime, endtime, type
1, 08:00, 09:30, class
1, 10:00, 11:30, class
2, 09:00, 12:00, exam
```
*Note: `dayofweek` can be numbers (1=Monday .. 7=Sunday) or day names (e.g. `Mon`, `Tuesday`). `type` options are `class`, `lab`, `break`, or `exam`.*

---

## 🚀 Pro Rust Cloud Acceleration Engine

For institutions with massive course matrices (> 100 courses, > 50 rooms), connect to the Pro Rust Cloud Microservice:
1. Navigate to **Site Administration ➔ Plugins ➔ Local plugins ➔ Schola Slots Settings**.
2. Enter your Pro License Key.
3. Once validated, solver computations execute off-server in < 50ms with zero memory overhead on your Moodle server.

---

## 💻 CLI Data Generator

Seed test academic data (Faculties, Courses, Faculty Accounts, Student Accounts, Rooms, and Slots) using the built-in CLI tool:

```bash
cd /path/to/moodle
php local/schola_slots/cli/populate_academic_data.php --courses=60 --teachers=35 --students=300
```

---

## 📞 Support & Contact

- **Support Email**: [wanyonyi.d.emanuel@gmail.com](mailto:wanyonyi.d.emanuel@gmail.com)
- **Website**: [https://scholaslots.com](https://scholaslots.com)
- **GitHub Repository**: [https://github.com/ewanyonyi/moodle-local_schola_slots](https://github.com/ewanyonyi/moodle-local_schola_slots)
- **Issue Tracker**: [https://github.com/ewanyonyi/moodle-local_schola_slots/issues](https://github.com/ewanyonyi/moodle-local_schola_slots/issues)
