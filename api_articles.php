<?php
/**
 * Public REST API: Fetch Published Articles and Issues
 * Returns JSON of published articles or issues
 */
header('Content-Type: application/json');
require_once __DIR__ . '/admin/config.php';

try {
    $db = getDB();
    $action = $_GET['action'] ?? 'articles';
    $id = intval($_GET['id'] ?? 0);
    $volume = intval($_GET['volume'] ?? 0);
    $issue = intval($_GET['issue'] ?? 0);

    // 1. Single Article Detail
    if ($action === 'detail' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM published_articles WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch();
        if ($article) {
            echo json_encode(['success' => true, 'article' => $article]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Article not found.']);
        }
        exit;
    }

    // 1b. Track Manuscript by Reference
    if ($action === 'track') {
        $ref = trim($_GET['ref'] ?? '');
        if (empty($ref)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid tracking reference.']);
            exit;
        }
        $stmt = $db->prepare("SELECT tracking_ref, manuscript_title, author_name, author_surname, status, created_at, reviewer_notes FROM submissions WHERE tracking_ref = :ref LIMIT 1");
        $stmt->execute([':ref' => $ref]);
        $submission = $stmt->fetch();
        if ($submission) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'tracking_ref'     => $submission['tracking_ref'],
                    'manuscript_title' => $submission['manuscript_title'],
                    'author'           => $submission['author_name'] . ' ' . $submission['author_surname'],
                    'status'           => $submission['status'],
                    'date'             => date('F d, Y', strtotime($submission['created_at'])),
                    'editorial_notes'  => !empty($submission['reviewer_notes']) ? $submission['reviewer_notes'] : null
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tracking reference not found. Please verify the code on your submission receipt.']);
        }
        exit;
    }

    // 2. Filtered by Volume & Issue (for Current Issue)
    if ($volume > 0 && $issue > 0) {
        $stmt = $db->prepare("SELECT * FROM published_articles WHERE volume = :v AND issue = :i ORDER BY id DESC");
        $stmt->execute([':v' => $volume, ':i' => $issue]);
        $articles = $stmt->fetchAll();
        echo json_encode(['success' => true, 'count' => count($articles), 'articles' => $articles]);
        exit;
    }

    // 3. All Published Articles (For Archives / Search / Home)
    $stmt = $db->query("SELECT * FROM published_articles ORDER BY year DESC, volume DESC, issue DESC, id DESC");
    $articles = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'count'    => count($articles),
        'articles' => $articles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
