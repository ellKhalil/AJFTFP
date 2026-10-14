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
