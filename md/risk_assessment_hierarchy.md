# Risk Assessment Hierarchy System

This document outlines the parent-child relationship of the Process Hazard Assessment (PHA) system in Sheener.

## Data Hierarchy

The health and safety assessment data is structured in a multi-level hierarchy to ensure comprehensive risk management.

### 1. Level 1: Process Hazard Assessment (Parent)
- **Database Table**: `process_hazard_assessments`
- **Description**: The top-level record for an assessment. It contains general metadata about the process being assessed.
- **Key Fields**:
    - `assessment_id` (Primary Identifier)
    - `assessment_code` (e.g., PHA-2023-001)
    - `process_name`
    - `assessment_date`
    - `status` (Draft, Final, Archived)

---

### 2. Level 2: Hazards (Direct Children)
- **Database Table**: `hazards`
- **Description**: Individual hazards identified during the process assessment. Each assessment can have multiple hazards.
- **Parent Relationship**: Linked via `assessment_id`.
- **Key Fields**:
    - `hazard_id` (Primary Identifier)
    - `hazard_description`
    - `initial_likelihood` / `initial_severity` (Risk Rating)
    - `task_id` (Associated work task)
    - `hazard_type_id`

---

### 3. Level 3: Controls (Grandchildren)
- **Database Table**: `controls`
- **Description**: Mitigation measures implemented to reduce the risk of a specific hazard. Each hazard can have multiple controls.
- **Parent Relationship**: Linked via `hazard_id`.
- **Key Fields**:
    - `control_id` (Primary Identifier)
    - `control_description`
    - `control_type_id` (Elimination, Administrative, PPE, etc.)
    - `status` (Implemented, Pending)

---

### 4. Level 4: Actions (Great-Grandchildren)
- **Database Table**: `hazard_control_actions`
- **Description**: Specific tasks required to implement or maintain a control.
- **Parent Relationship**: Linked via `control_id` (and indirectly to `hazard_id`).
- **Key Fields**:
    - `action_id` (Primary Identifier)
    - `description`
    - `owner_id` (Person responsible)
    - `due_date`
    - `status`

---

## Secondary Relationships (Linked to Parent)

### Assessors
- **Database Table**: `ra_assessorlinkt`
- **Relationship**: Many-to-Many link between Assessments and People.
- **Function**: Records which subject matter experts were involved in the assessment.
- **Key Fields**: `RAID` (Assessment ID), `AssessorID` (Person ID), `AssessDate`.

### Signoffs
- **Database Table**: `hazard_assessment_signoffs`
- **Relationship**: 1-to-Many.
- **Function**: Records the electronic approval of the parent assessment.
- **Key Fields**: `signer_role`, `signer_id`, `signature_date`.
