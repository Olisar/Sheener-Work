# Process Map Schema Analysis & Integration Plan

## Executive Summary

This document provides a comprehensive analysis of the current database schema's support for linking critical operational elements (Events, Documents, Permit to Work, Opportunities for Improvement, Risk Assessments, and Tasks) to the core process structure (`process_map`). The analysis identifies gaps, weaknesses, and provides a structured implementation plan.

**Date:** 2024-12-19  
**Status:** Analysis Complete - Implementation Required

---

## 1. Current State Analysis

### 1.1 Core Process Structure

**Table:** `process_map`
- **Purpose:** Hierarchical process structure (process → step → substep → task → activity)
- **Key Fields:**
  - `id` (PK, int(11))
  - `type` (enum: 'process','step','substep','task','activity')
  - `text` (varchar(255))
  - `parent` (FK to process_map.id)
- **Status:** ✅ Well-structured, supports hierarchical relationships

### 1.2 Existing Linkage Tables

The system currently has the following junction tables linking to `process_map`:

| Junction Table | Links To | Status | Notes |
|---------------|---------|--------|-------|
| `process_map_document` | `documents` | ✅ EXISTS | Fully functional |
| `process_map_risk` | `risks` | ✅ EXISTS | Links individual risks |
| `process_map_people` | `people` | ✅ EXISTS | Links personnel |
| `process_map_equipment` | `equipment` | ✅ EXISTS | Links equipment |
| `process_map_material` | `materials` | ✅ EXISTS | Links materials |
| `process_map_energy` | `energy` | ✅ EXISTS | Links energy sources |
| `process_map_area` | `areas` | ✅ EXISTS | Links areas/locations |
| `process_map_batch_quantity` | `batches` | ✅ EXISTS | Links batch quantities |
| `process_map_sop` | `sop_data` | ✅ EXISTS | Links SOPs |

---

## 2. Gap Analysis: Required Linkages

### 2.1 Events ❌ MISSING

**Current State:**
- Two event tables exist:
  - `events` (event_id, event_type: 'OFI','Adverse Event','Defects','NonCompliance')
  - `operational_events` (event_id, event_type: 'Incident','Finding','OFI','Observation')
- **No direct linkage to `process_map`**

**Impact:**
- Cannot associate events with specific process steps
- Cannot track which processes are affected by incidents
- Cannot link OFIs to process improvements
- Limited traceability and root cause analysis

**Required Solution:**
- Create `process_map_event` junction table
- Create `process_map_operational_event` junction table
- Support multiple events per process node (many-to-many)

---

### 2.2 Permit to Work ❌ MISSING

**Current State:**
- `permits` table exists (permit_id, task_id, permit_type, status)
- Permits are linked to `tasks` table, but not to `process_map`
- **No direct linkage to `process_map`**

**Impact:**
- Cannot associate permits with process steps
- Cannot track which processes require permits
- Limited visibility into permit requirements across processes
- Difficult to ensure compliance at process level

**Required Solution:**
- Create `process_map_permit` junction table
- Support multiple permits per process node
- Enable process-level permit tracking

---

### 2.3 Opportunities for Improvement (OFI) ❌ MISSING

**Current State:**
- `ofi_details` table exists (ofi_id, event_id, recommended_improvement, implementation_status)
- OFIs are linked to `operational_events`, but not to `process_map`
- **No direct linkage to `process_map`**

**Impact:**
- Cannot associate OFIs with specific process steps
- Cannot track which processes have improvement opportunities
- Limited ability to prioritize improvements by process impact
- Difficult to measure improvement effectiveness at process level

**Required Solution:**
- Create `process_map_ofi` junction table
- Link OFIs directly to process_map nodes
- Support tracking implementation status at process level

---

### 2.4 Risk Assessments ⚠️ PARTIAL

**Current State:**
- `process_map_risk` exists and links to `risks` table
- However, `risks` table links to `hazards`, which link to `tasks`
- `hira_register` table exists for formal risk assessments but has no link to `process_map`
- **Partial linkage exists, but HIRA assessments are not linked**

**Impact:**
- Individual risks can be linked, but formal HIRA assessments cannot
- Cannot track which processes have been formally assessed
- Limited visibility into assessment coverage
- Risk assessment scope includes 'Task' but not process_map nodes

**Required Solution:**
- Create `process_map_hira` junction table for HIRA assessments
- Verify `process_map_risk` covers all risk types
- Consider adding risk assessment metadata to junction table

---

### 2.5 Tasks ❌ MISSING

**Current State:**
- `tasks` table exists (task_id, task_name, status, priority, etc.)
- Tasks can be linked to projects, departments, change requests
- **No direct linkage to `process_map`**

**Impact:**
- Cannot associate tasks with process steps
- Cannot track which processes have pending/completed tasks
- Limited visibility into process execution status
- Difficult to manage process-related work items

**Required Solution:**
- Create `process_map_task` junction table
- Support multiple tasks per process node
- Enable task tracking at process level

---

### 2.6 Documents ✅ EXISTS

**Current State:**
- `process_map_document` junction table exists
- Links `process_map` to `documents` table
- **Fully functional**

**Status:** ✅ No action required

---

## 3. Schema Design: New Junction Tables

### 3.1 Design Principles

1. **Consistency:** Follow existing junction table naming convention (`process_map_*`)
2. **Referential Integrity:** Use CASCADE DELETE to maintain data consistency
3. **Indexing:** Index foreign keys for performance
4. **Extensibility:** Allow for future metadata fields if needed
5. **Many-to-Many:** Support multiple links per process node

### 3.2 Proposed Junction Tables

#### 3.2.1 `process_map_event`
```sql
CREATE TABLE `process_map_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_event` (`process_map_id`, `event_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_event_id` (`event_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_event_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_event_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_event_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 3.2.2 `process_map_operational_event`
```sql
CREATE TABLE `process_map_operational_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `operational_event_id` int(11) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_operational_event` (`process_map_id`, `operational_event_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_operational_event_id` (`operational_event_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_operational_event_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_operational_event_ibfk_2` FOREIGN KEY (`operational_event_id`) REFERENCES `operational_events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_operational_event_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 3.2.3 `process_map_permit`
```sql
CREATE TABLE `process_map_permit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `permit_id` int(11) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_permit` (`process_map_id`, `permit_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_permit_id` (`permit_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_permit_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_permit_ibfk_2` FOREIGN KEY (`permit_id`) REFERENCES `permits` (`permit_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_permit_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 3.2.4 `process_map_ofi`
```sql
CREATE TABLE `process_map_ofi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `ofi_id` int(11) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_ofi` (`process_map_id`, `ofi_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_ofi_id` (`ofi_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_ofi_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_ofi_ibfk_2` FOREIGN KEY (`ofi_id`) REFERENCES `ofi_details` (`ofi_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_ofi_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 3.2.5 `process_map_task`
```sql
CREATE TABLE `process_map_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_task` (`process_map_id`, `task_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_task_id` (`task_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_task_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_task_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_task_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 3.2.6 `process_map_hira` (for HIRA assessments)
```sql
CREATE TABLE `process_map_hira` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` int(11) NOT NULL,
  `hira_id` bigint(20) NOT NULL,
  `linked_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_process_hira` (`process_map_id`, `hira_id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_hira_id` (`hira_id`),
  KEY `ix_linked_by` (`linked_by`),
  CONSTRAINT `process_map_hira_ibfk_1` FOREIGN KEY (`process_map_id`) REFERENCES `process_map` (`id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_hira_ibfk_2` FOREIGN KEY (`hira_id`) REFERENCES `hira_register` (`hira_id`) ON DELETE CASCADE,
  CONSTRAINT `process_map_hira_ibfk_3` FOREIGN KEY (`linked_by`) REFERENCES `people` (`people_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 4. Implementation Plan

### Phase 1: Schema Updates (Priority: HIGH)
1. ✅ Create migration script with all new junction tables
2. ✅ Test migration on development database
3. ✅ Verify foreign key constraints
4. ✅ Create indexes for performance
5. ✅ Document schema changes

### Phase 2: API Updates (Priority: HIGH)
1. ✅ Update `api_process_map.php` to support new linkages
2. ✅ Add CRUD operations for new junction tables
3. ✅ Add bulk assignment capabilities
4. ✅ Update node detail endpoints to include new relationships
5. ✅ Add filtering/search by linked entities

### Phase 3: Frontend Integration (Priority: MEDIUM)
1. ⏳ Update process map UI to display new linkages
2. ⏳ Add UI for linking/unlinking entities
3. ⏳ Add filters for events, permits, OFIs, tasks
4. ⏳ Update node detail sidebar to show all linked entities
5. ⏳ Add bulk assignment UI

### Phase 4: Testing & Validation (Priority: HIGH)
1. ⏳ Unit tests for new junction tables
2. ⏳ Integration tests for API endpoints
3. ⏳ Data integrity validation
4. ⏳ Performance testing with large datasets
5. ⏳ User acceptance testing

### Phase 5: Documentation & Training (Priority: MEDIUM)
1. ⏳ Update API documentation
2. ⏳ Create user guide for linking entities
3. ⏳ Document data migration procedures
4. ⏳ Training materials for end users

---

## 5. Risk Assessment & Mitigation

### 5.1 Data Integrity Risks

**Risk:** Orphaned records if foreign keys are not properly configured  
**Mitigation:** Use CASCADE DELETE and proper foreign key constraints

**Risk:** Duplicate links between same entities  
**Mitigation:** Use UNIQUE constraints on (process_map_id, entity_id) combinations

**Risk:** Performance degradation with large datasets  
**Mitigation:** Proper indexing on foreign keys and frequently queried fields

### 5.2 Migration Risks

**Risk:** Data loss during migration  
**Mitigation:** 
- Backup database before migration
- Test migration on development environment
- Use transactions for atomic operations
- Create rollback script

**Risk:** Application downtime  
**Mitigation:**
- Schedule migration during maintenance window
- Use feature flags to enable new functionality gradually

### 5.3 Compatibility Risks

**Risk:** Breaking changes to existing API  
**Mitigation:**
- Maintain backward compatibility
- Version API endpoints
- Deprecate old endpoints gradually

---

## 6. Success Criteria

✅ All required junction tables created and functional  
✅ API endpoints support CRUD operations for all new linkages  
✅ Data integrity maintained (no orphaned records)  
✅ Performance acceptable (< 100ms for typical queries)  
✅ Frontend displays all linked entities correctly  
✅ Users can link/unlink entities through UI  
✅ Documentation complete and accurate  

---

## 7. Next Steps

1. **Immediate:** Review and approve schema design
2. **Week 1:** Create and test migration script
3. **Week 2:** Update API endpoints
4. **Week 3:** Frontend integration
5. **Week 4:** Testing and bug fixes
6. **Week 5:** Documentation and deployment

---

## 8. Appendix: Entity Relationship Summary

```
process_map (core structure)
├── process_map_document ✅
├── process_map_risk ✅
├── process_map_people ✅
├── process_map_equipment ✅
├── process_map_material ✅
├── process_map_energy ✅
├── process_map_area ✅
├── process_map_batch_quantity ✅
├── process_map_sop ✅
├── process_map_event ❌ (NEW)
├── process_map_operational_event ❌ (NEW)
├── process_map_permit ❌ (NEW)
├── process_map_ofi ❌ (NEW)
├── process_map_task ❌ (NEW)
└── process_map_hira ❌ (NEW)
```

---

**Document Version:** 1.0  
**Last Updated:** 2024-12-19  
**Author:** System Analysis  
**Status:** Ready for Implementation

