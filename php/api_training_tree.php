<?php
/* File: sheener/php/api_training_tree.php */

// api_training_tree.php
// Returns hierarchical structure of Documents -> Versions -> Quizzes -> Questions

header('Content-Type: application/json');
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Fetch all documents
    $docsStmt = $pdo->query("SELECT DocumentID, Title, DocCode FROM documents WHERE StatusID = 1 ORDER BY Title");
    $documents = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

    $treeData = [];

    foreach ($documents as $doc) {
        $docNode = [
            'id' => 'doc_' . $doc['DocumentID'],
            'type' => 'document',
            'title' => $doc['Title'],
            'code' => $doc['DocCode'],
            'children' => []
        ];

        // Fetch Versions for this document
        $versStmt = $pdo->prepare("SELECT VersionID, VersionNumber, OriginalFilename, RevisionLabel FROM documentversions WHERE DocumentID = ? ORDER BY VersionNumber DESC");
        $versStmt->execute([$doc['DocumentID']]);
        $versions = $versStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($versions as $ver) {
            $verNode = [
                'id' => 'ver_' . $ver['VersionID'],
                'type' => 'version',
                'title' => 'Version ' . $ver['VersionNumber'] . ($ver['RevisionLabel'] ? ' (' . $ver['RevisionLabel'] . ')' : ''),
                'filename' => $ver['OriginalFilename'],
                'children' => []
            ];

            // Fetch Quizzes for this version
            $quizStmt = $pdo->prepare("SELECT id, title, passing_score FROM quizzes WHERE doc_version_id = ? AND active = 1");
            $quizStmt->execute([$ver['VersionID']]);
            $quizzes = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($quizzes as $quiz) {
                $quizNode = [
                    'id' => 'quiz_' . $quiz['id'],
                    'type' => 'quiz',
                    'title' => $quiz['title'],
                    'passing_score' => $quiz['passing_score'],
                    'children' => []
                ];

                // Fetch Questions for this quiz
                $quesStmt = $pdo->prepare("SELECT id, question_text, question_type FROM quiz_questions WHERE quiz_id = ? ORDER BY display_order");
                $quesStmt->execute([$quiz['id']]);
                $questions = $quesStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($questions as $q) {
                    $quizNode['children'][] = [
                        'id' => 'q_' . $q['id'],
                        'type' => 'question',
                        'title' => $q['question_text'], // Using title for consistency in rendering
                        'question_type' => $q['question_type']
                    ];
                }

                $verNode['children'][] = $quizNode;
            }

            $docNode['children'][] = $verNode;
        }

        $treeData[] = $docNode;
    }

    echo json_encode(['success' => true, 'data' => $treeData]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
