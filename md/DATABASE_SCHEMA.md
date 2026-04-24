# 🗄️ SHEEner Data Architecture & Schema

**Version:** 1.0  
**Context:** SQL Database Design  

---

## 🏛️ Core Entity Relationships

The system is built on a relational MySQL database. The primary focus is the integration of the **Process Map** with operational and compliance data.

### 🧩 6Ps Registry (Master Data)
Master tables provide the foundation for all operational records.
*   **People (`people`)**: Personnel registry, position, department.
*   **Products (`materials`)**: Materials and chemical registry.
*   **Places (`areas`)**: Facility locations and sites.
*   **Plants (`plants`)**: Equipment and machinery assets.
*   **Processes (`documents`)**: SOPs and procedural documents.
*   **Power (`energy`)**: Energy and utility tracking.

---

## 🗺️ Process Mapping & Junctions

The `process_map` table is a self-referencing hierarchical structure (Parent/ID) that supports unlimited depth. To link this map to operational data, the following junction tables are used:

| Junction Table | Target Entity | Purpose |
| :--- | :--- | :--- |
| `process_map_event` | `events` | Linking incidents to specific process steps. |
| `process_map_task` | `tasks` | Assigning compliance tasks to process nodes. |
| `process_map_permit` | `permit_list` | Mapping Permits-to-Work (PTW) to equipment/processes. |
| `process_map_hira` | `assessments` | Linking risk assessments to operational steps. |
| `process_map_activity` | `activities` | Connecting manufacturing activities to the map. |

---

## 🔒 Security & RBAC Schema

The security model is decoupled from core entities to support flexible role assignments.

*   **`people_roles`**: Authoritative mapping of users to roles (Multi-role support).
*   **`roles`**: Role definitions (e.g., Admin, Approver, Supervisor).
*   **`rolepermissions`**: Mapping roles to granular permissions.
*   **`permissions`**: Definition of specific actions (e.g., `edit_tasks`, `approve_permit`).

---

## 📝 Governance & Audit

### Audit Logging
Every mutation in the system is captured in the global audit logs:
*   **`auditlogs`**: Generic action tracking (Who, What, When).
*   **`process_map_audit`**: Structural change logging for the hierarchical map (Old/New values).

### Approvals
The `approvals` table manages the workflow state for all high-risk or controlled entities, linked to `approvalstatuses`.

---

## 📂 Reference Files
*   **Full Schema JSON**: [`database_schema.json`](file:///d:/xampp/htdocs/sheener/database_schema.json)
*   **SQL Migrations**: `sql/database_migrations/`
*   **RBAC Specifics**: [`docs/RBAC_STRUCTURE.md`](file:///d:/xampp/htdocs/sheener/docs/RBAC_STRUCTURE.md)
