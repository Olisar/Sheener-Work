# Task Questionnaire Setup Guide

## Overview
This implementation adds a pharma job pre-screening questionnaire to the task creation process. The questionnaire automatically:
- Flags notifiable projects based on duration/person-hours
- Creates hazard records based on questionnaire answers
- Recommends permits based on identified hazards
- Optionally creates HIRA register entries

## Database Setup

### Step 1: Create the taskquestionnaire table

Run the following SQL script to create the required table:

```sql
CREATE TABLE IF NOT EXISTS `taskquestionnaire` (
  `taskquestionnaireid` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `notifiableflag` tinyint(1) DEFAULT 0,
  `estimateddurationdays` int(11) DEFAULT NULL,
  `estimatedpersonhours` int(11) DEFAULT NULL,
  `keyhazardsjson` text DEFAULT NULL,
  `recommendhiraflag` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`taskquestionnaireid`),
  UNIQUE KEY `taskid` (`taskid`),
  CONSTRAINT `taskquestionnaire_ibfk_1` FOREIGN KEY (`taskid`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

You can run this SQL file: `php/create_taskquestionnaire_table.sql`

### Step 2: Verify Hazard Types

The system will automatically create hazard types if they don't exist, but you may want to ensure these exist:
- Work at Height
- Confined Space
- Chemical Exposure Hazard
- ATEX / Classified Area
- Hot Work
- Electrical/Static Hazard
- Energy Isolation

## Features Implemented

### 1. Add Task Modal
- Collapsible "Job Pre-screen (Pharma)" section
- 8 questions covering:
  - Estimated duration
  - Estimated person-hours
  - High-hazard chemicals
  - Work at height
  - Confined spaces
  - ATEX/sterile areas
  - Critical utilities (multi-select)
  - Hot work
- Validation warning for high-priority tasks

### 2. Backend Processing
- Saves questionnaire data to `taskquestionnaire` table
- Automatically creates hazard records based on answers
- Calculates notifiable flag (duration >30 days OR person-hours >500)
- Generates permit recommendations
- Optionally creates HIRA register entry

### 3. View Task Modal
- Displays questionnaire results
- Shows notifiable flag
- Displays key hazards as badges
- Lists suggested permits with "Create" buttons
- Shows notes if provided

## Mapping Rules

### Notifiable Project
- Duration >30 days OR Person-hours >500 → `notifiableflag = 1`

### Hazards Created
- Work at height = Yes → Creates "Work at Height" hazard
- Confined space = Yes → Creates "Confined Space" hazard
- Potent API = Yes → Creates "Chemical Exposure Hazard"
- ATEX = Yes → Creates "ATEX / Classified Area" hazard
- Hot work = Yes → Creates "Hot Work" hazard
- Utilities (electrical) = Yes → Creates "Electrical/Static Hazard"
- Utilities (steam/air/gases) = Yes → Creates "Energy Isolation" hazard

### Permit Recommendations
- Work at height → "Work at Height" permit
- Confined space → "Confined Space" permit
- Hot work → "Hot Work" permit
- Electrical → "Electrical Work" permit
- Energy isolation → "Clearance" permit

## Usage

1. When creating a new task, expand the "Job Pre-screen Questionaire" section
2. Answer the questions (all optional, but recommended for high-priority tasks)
3. Submit the task
4. The system will automatically:
   - Save questionnaire data
   - Create hazard records
   - Generate permit recommendations
   - Create HIRA register entry (if hazards identified)

5. When viewing a task, the "Job Pre-screen" section shows:
   - Notifiable status
   - Key hazards
   - Suggested permits (with create buttons)

## Notes

- The questionnaire is optional but strongly recommended for High/Critical priority tasks
- The system will prompt users if they try to save a high-priority task without completing the questionnaire
- Hazard types are created automatically if they don't exist
- Permit recommendations are stored in the questionnaire JSON and displayed in the view modal
- Users can create permits directly from the suggested permits list

