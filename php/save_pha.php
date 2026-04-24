<?php
/* File: sheener/php/save_pha.php */

header('Content-Type: application/json');
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $assessment_id = isset($_POST['assessment_id']) && !empty($_POST['assessment_id']) ? intval($_POST['assessment_id']) : null;
        $assessment_code = $_POST['assessment_code'] ?? null;
        $process_name = $_POST['process_name'] ?? null;
        $process_overview = $_POST['process_overview'] ?? null;
        $assessment_date = $_POST['assessment_date'] ?? null;
        $assessed_by_id = isset($_POST['assessed_by_id']) && !empty($_POST['assessed_by_id']) ? intval($_POST['assessed_by_id']) : null;

        $hazards = isset($_POST['hazards']) ? $_POST['hazards'] : [];
        $assessors = isset($_POST['assessors']) ? $_POST['assessors'] : [];
        
        if (empty($assessment_code) || empty($process_name)) {
            echo json_encode(['success' => false, 'error' => 'Missing required assessment information']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        if ($assessment_id) {
            $pdo->prepare("UPDATE process_hazard_assessments SET assessment_code = :c, process_name = :n, process_overview = :o, assessment_date = :d, assessed_by_id = :a, updated_at = CURRENT_TIMESTAMP WHERE assessment_id = :id")
                ->execute([':c' => $assessment_code, ':n' => $process_name, ':o' => $process_overview, ':d' => $assessment_date, ':a' => $assessed_by_id, ':id' => $assessment_id]);
        } else {
            $pdo->prepare("INSERT INTO process_hazard_assessments (assessment_code, process_name, process_overview, assessment_date, assessed_by_id, status) VALUES (:c, :n, :o, :d, :a, 'Draft')")
                ->execute([':c' => $assessment_code, ':n' => $process_name, ':o' => $process_overview, ':d' => $assessment_date, ':a' => $assessed_by_id]);
            $assessment_id = $pdo->lastInsertId();
        }
        
        $postHazardIds = [];
        foreach ($hazards as $h) if (!empty($h['hazard_id'])) $postHazardIds[] = intval($h['hazard_id']);
        
        if ($assessment_id) {
            $hNot = !empty($postHazardIds) ? " AND hazard_id NOT IN (".implode(',', $postHazardIds).")" : "";
            $pdo->prepare("DELETE FROM hazard_control_actions WHERE hazard_id IN (SELECT hazard_id FROM hazards WHERE assessment_id = :aid $hNot)")->execute([':aid' => $assessment_id]);
            $pdo->prepare("DELETE FROM controls WHERE hazard_id IN (SELECT hazard_id FROM hazards WHERE assessment_id = :aid $hNot)")->execute([':aid' => $assessment_id]);
            $pdo->prepare("DELETE FROM hazards WHERE assessment_id = :aid $hNot")->execute([':aid' => $assessment_id]);
        }

        foreach ($hazards as $hData) {
            $h_id = !empty($hData['hazard_id']) ? intval($hData['hazard_id']) : null;
            $h_desc = $hData['description'] ?? '';
            if (empty($h_desc)) continue;

            $params = [
                ':aid' => $assessment_id,
                ':tid' => !empty($hData['task_id']) ? intval($hData['task_id']) : null,
                ':htid' => !empty($hData['hazard_type_id']) ? intval($hData['hazard_type_id']) : null,
                ':desc' => $h_desc,
                ':step' => $hData['process_step'] ?? null,
                ':ctrl' => $hData['existing_controls'] ?? null,
                ':l' => !empty($hData['likelihood']) ? intval($hData['likelihood']) : null,
                ':s' => !empty($hData['severity']) ? intval($hData['severity']) : null,
                ':rl' => !empty($hData['residual_likelihood']) ? intval($hData['residual_likelihood']) : null,
                ':rs' => !empty($hData['residual_severity']) ? intval($hData['residual_severity']) : null
            ];

            if ($h_id) {
                $params[':hid'] = $h_id;
                $pdo->prepare("UPDATE hazards SET task_id=:tid, hazard_type_id=:htid, hazard_description=:desc, process_step=:step, existing_controls=:ctrl, initial_likelihood=:l, initial_severity=:s, residual_likelihood=:rl, residual_severity=:rs WHERE hazard_id=:hid AND assessment_id=:aid")
                    ->execute($params);
            } else {
                $pdo->prepare("INSERT INTO hazards (assessment_id, task_id, hazard_type_id, hazard_description, process_step, existing_controls, initial_likelihood, initial_severity, residual_likelihood, residual_severity) VALUES (:aid, :tid, :htid, :desc, :step, :ctrl, :l, :s, :rl, :rs)")
                    ->execute($params);
                $h_id = $pdo->lastInsertId();
            }

            $controls = $hData['controls'] ?? [];
            $postCtrlIds = [];
            foreach ($controls as $c) if (!empty($c['control_id'])) $postCtrlIds[] = intval($c['control_id']);
            
            $cNot = !empty($postCtrlIds) ? " AND control_id NOT IN (".implode(',', $postCtrlIds).")" : "";
            $pdo->prepare("DELETE FROM hazard_control_actions WHERE control_id IN (SELECT control_id FROM controls WHERE hazard_id = :hid $cNot)")->execute([':hid' => $h_id]);
            $pdo->prepare("DELETE FROM controls WHERE hazard_id = :hid $cNot")->execute([':hid' => $h_id]);

            foreach ($controls as $cData) {
                $c_id = !empty($cData['control_id']) ? intval($cData['control_id']) : null;
                $c_desc = $cData['description'] ?? '';
                if (empty($c_desc)) continue;

                $cat = $cData['category'] ?? null;
                $ct_id = is_numeric($cat) ? intval($cat) : null;
                if (!$ct_id && !empty($cat)) {
                    $st = $pdo->prepare("SELECT control_type_id FROM control_types WHERE type_name = :n LIMIT 1");
                    $st->execute([':n' => $cat]);
                    $r = $st->fetch(); if ($r) $ct_id = $r['control_type_id'];
                }
                if (!$ct_id) $ct_id = 1;

                $cp = [':hid' => $h_id, ':desc' => $c_desc, ':tid' => $ct_id, ':status' => $cData['status'] ?? 'Pending'];
                if ($c_id) { $cp[':cid'] = $c_id; $pdo->prepare("UPDATE controls SET control_description=:desc, control_type_id=:tid, status=:status WHERE control_id=:cid AND hazard_id=:hid")->execute($cp); }
                else { $pdo->prepare("INSERT INTO controls (hazard_id, control_description, control_type_id, status) VALUES (:hid, :desc, :tid, :status)")->execute($cp); }
            }
        }
        
        $pdo->prepare("DELETE FROM ra_assessorlinkt WHERE RAID = ?")->execute([$assessment_id]);
        foreach ($assessors as $ass) {
            $pid = !empty($ass['person_id']) ? intval($ass['person_id']) : null;
            if ($pid) $pdo->prepare("INSERT INTO ra_assessorlinkt (RAID, AssessorID, AssessDate) VALUES (?, ?, ?)")->execute([$assessment_id, $pid, $ass['assess_date'] ?? null]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'assessment_id' => $assessment_id]);
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
