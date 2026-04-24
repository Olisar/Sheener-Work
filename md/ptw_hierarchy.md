# Permit To Work (PTW) Hierarchy System

This document outlines the parent-child relationship of the Permit To Work (PTW) system in Sheener.

## Data Hierarchy

The PTW system is centered around a primary Permit record, which serves as the "Main Permit" or "Master Permit" that can link to various child records and sub-permits.

### 1. Level 1: Permit (Parent)
- **Database Table**: `permits`
- **Description**: The core record for a work permit. It contains the permit type, validity dates, status, and high-level metadata.
- **Key Fields**:
    - `permit_id` (Primary Identifier)
    - `permit_type` (Clearance, Hot Work, Confined Space, etc.)
    - `issue_date` / `expiry_date`
    - `status` (Active, Pending, Expired, Completed)
    - `issued_by` / `approved_by` (Links to `people`)
    - `task_id` (Links to `tasks`)

---

### 2. Level 2: Permit Steps (Direct Children)
- **Database Table**: `permit_steps`
- **Description**: Known in the UI as the **"Safe Plan of Action"**. It contains a sequence of steps required to perform the job safely.
- **Parent Relationship**: Linked via `permit_id`.
- **Key Fields**:
    - `step_id`
    - `step_number` (Sequence)
    - `step_description`
    - `hazard_description` (Hazards specific to this step)
    - `control_description` (Controls specific to this step)

---

### 3. Level 2: Sub-Permits (Linked Children)
- **Database Table**: `sub_permits` (Join table)
- **Description**: Allows a "Main Permit" to be associated with multiple specialized "Sub-Permits" (e.g., a Confined Space permit associated with a general Maintenance permit).
- **Structure**:
    - Uses a many-to-many style mapping via the `sub_permits` table.
- **Key Fields**:
    - `main_permit_id` (The parent permit)
    - `permit_id` (The child permit)

---

### 4. Level 2: Attachments (Children)
- **Database Table**: `attachments`
- **Description**: Files, certificates, or photos related to the permit.
- **Parent Relationship**: Linked via `permit_id`.
- **Key Fields**:
    - `file_name` / `file_path`
    - `permit_id`

---

### 5. Level 2: Audit Logs (Children)
- **Database Table**: `permit_audit_logs`
- **Description**: A tracking table for all actions performed on the permit (creation, approval, status changes).
- **Parent Relationship**: Linked via `permit_id`.
- **Key Fields**:
    - `action` (e.g., "Approved")
    - `user_id` (Who performed the action)
    - `timestamp`

---

## Secondary Relationships

### Linked Tasks and Projects
- **Tables**: `tasks`, `projects`
- **Function**: Permits are usually linked to a specific Task, which in turn belongs to a Project. This provides the "Business Context" for the permit.

### People and Roles
- **Table**: `people`
- **Function**: Linked via `issued_by`, `approved_by`, and `Dep_owner` to record the human chain of responsibility.
