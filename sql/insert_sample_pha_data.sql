/* File: sheener/insert_sample_pha_data.sql */
-- Insert sample data for Process Hazard Assessments
-- This script inserts two sample PHAs with linked hazards, controls, actions, and signoffs

-- Insert first Process Hazard Assessment
INSERT INTO `process_hazard_assessments` (
    `assessment_code`, `process_name`, `process_overview`, `assessment_date`, `assessed_by_id`, `status`
) VALUES (
    'PHA-2025-001', 
    'Chemical Mixing Process', 
    'Assessment of the chemical mixing process in the production line, including raw material handling and mixing operations.', 
    '2025-12-01', 
    1, -- Assuming people_id 1 exists
    'Approved'
);

-- Insert hazards for first assessment one by one
INSERT INTO `hazards` (
    `task_id`, `hazard_type_id`, `hazard_description`, `assessment_id`, `process_step`, `existing_controls`, `initial_likelihood`, `initial_severity`, `residual_likelihood`, `residual_severity`
) VALUES
(2, 1, 'Chemical spillage during mixing', 1, 'Material transfer', 'Safety goggles, gloves, spill containment trays', 3, 4, 2, 2);

INSERT INTO `hazards` (
    `task_id`, `hazard_type_id`, `hazard_description`, `assessment_id`, `process_step`, `existing_controls`, `initial_likelihood`, `initial_severity`, `residual_likelihood`, `residual_severity`
) VALUES
(2, 2, 'Equipment failure causing pressure buildup', 1, 'Mixing operation', 'Pressure relief valves, regular maintenance', 2, 5, 1, 3);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(1, 'Install additional spill containment barriers', 1, 'Implemented', '2025-11-15', '2026-11-15', 2);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(1, 'Train operators on spill response procedures', 3, 'In Progress', NULL, '2026-01-15', 3);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(2, 'Upgrade pressure monitoring system', 2, 'Pending', NULL, '2026-02-15', 2);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(2, 'Implement predictive maintenance program', 1, 'Implemented', '2025-10-20', '2026-10-20', 4);

-- Insert actions for controls
INSERT INTO `hazard_control_actions` (
    `hazard_id`, `control_id`, `description`, `owner_id`, `due_date`, `status`, `completion_date`
) VALUES
(1, (SELECT control_id FROM controls WHERE control_description = 'Install additional spill containment barriers' AND hazard_id = 1), 'Procure and install spill containment barriers', 2, '2025-12-15', 'Completed', '2025-12-10'),
(1, (SELECT control_id FROM controls WHERE control_description = 'Train operators on spill response procedures' AND hazard_id = 1), 'Develop training materials for spill response', 3, '2026-01-10', 'In Progress', NULL),
(2, (SELECT control_id FROM controls WHERE control_description = 'Upgrade pressure monitoring system' AND hazard_id = 2), 'Evaluate pressure monitoring system vendors', 2, '2026-01-20', 'Pending', NULL),
(2, (SELECT control_id FROM controls WHERE control_description = 'Implement predictive maintenance program' AND hazard_id = 2), 'Schedule maintenance training for technicians', 4, '2025-11-30', 'Completed', '2025-11-25');

-- Insert signoffs for first assessment
INSERT INTO `hazard_assessment_signoffs` (
    `assessment_id`, `signer_role`, `signer_id`
) VALUES
(1, 'Assessed By', 1),
(1, 'Reviewed By', 5),
(1, 'Approved By', 6);

-- Insert second Process Hazard Assessment
INSERT INTO `process_hazard_assessments` (
    `assessment_code`, `process_name`, `process_overview`, `assessment_date`, `assessed_by_id`, `status`
) VALUES (
    'PHA-2025-002', 
    'Heat Treatment Process', 
    'Assessment of the heat treatment process including furnace operations and material handling.', 
    '2025-12-05', 
    7, -- Assuming people_id 7 exists
    'In Review'
);

-- Insert hazards for second assessment one by one
INSERT INTO `hazards` (
    `task_id`, `hazard_type_id`, `hazard_description`, `assessment_id`, `process_step`, `existing_controls`, `initial_likelihood`, `initial_severity`, `residual_likelihood`, `residual_severity`
) VALUES
(2, 3, 'Burn hazards from hot surfaces', 2, 'Furnace loading/unloading', 'Heat-resistant gloves, protective clothing, guards', 4, 3, 2, 2);

INSERT INTO `hazards` (
    `task_id`, `hazard_type_id`, `hazard_description`, `assessment_id`, `process_step`, `existing_controls`, `initial_likelihood`, `initial_severity`, `residual_likelihood`, `residual_severity`
) VALUES
(2, 4, 'Explosion risk from gas leaks', 2, 'Gas supply system', 'Gas detectors, emergency shutdown systems', 1, 5, 1, 2);

-- Insert controls for second assessment hazards one by one
INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(3, 'Install automated temperature monitoring', 2, 'Implemented', '2025-11-01', '2026-11-01', 8);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(3, 'Enhance PPE training program', 3, 'In Progress', NULL, '2026-03-01', 9);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(4, 'Upgrade gas detection system', 2, 'Pending', NULL, '2026-04-01', 8);

INSERT INTO `controls` (
    `hazard_id`, `control_description`, `control_type_id`, `status`, `implementation_date`, `review_date`, `responsible_person_id`
) VALUES
(4, 'Implement gas leak drill procedures', 3, 'Reviewed', '2025-12-01', '2026-12-01', 10);

-- Insert actions for second assessment controls
INSERT INTO `hazard_control_actions` (
    `hazard_id`, `control_id`, `description`, `owner_id`, `due_date`, `status`, `completion_date`
) VALUES
(3, (SELECT control_id FROM controls WHERE control_description = 'Install automated temperature monitoring' AND hazard_id = 3), 'Install temperature sensors at key points', 8, '2025-12-20', 'Completed', '2025-12-15'),
(3, (SELECT control_id FROM controls WHERE control_description = 'Enhance PPE training program' AND hazard_id = 3), 'Create PPE training curriculum', 9, '2026-02-15', 'In Progress', NULL),
(4, (SELECT control_id FROM controls WHERE control_description = 'Upgrade gas detection system' AND hazard_id = 4), 'Procure advanced gas detectors', 8, '2026-03-15', 'Pending', NULL),
(4, (SELECT control_id FROM controls WHERE control_description = 'Implement gas leak drill procedures' AND hazard_id = 4), 'Conduct emergency response training', 10, '2026-01-15', 'Completed', '2025-12-20');

-- Insert signoffs for second assessment
INSERT INTO `hazard_assessment_signoffs` (
    `assessment_id`, `signer_role`, `signer_id`
) VALUES
(2, 'Assessed By', 7),
(2, 'Reviewed By', 11);