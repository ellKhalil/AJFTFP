<?php
/**
 * AJAX Manuscript Submission Endpoint for Authors
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $db = getDB();

    // Extract POST fields
    $author_title     = trim($_POST['author_title'] ?? '');
    $author_name      = trim($_POST['author_name'] ?? '');
    $author_surname   = trim($_POST['author_surname'] ?? '');
    $author_email     = trim($_POST['author_email'] ?? '');
    $author_phone     = trim($_POST['author_phone'] ?? '');
    $affiliation      = trim($_POST['affiliation'] ?? '');
    $country          = trim($_POST['country'] ?? 'Nigeria');
    $manuscript_title = trim($_POST['manuscript_title'] ?? '');
    $running_title    = trim($_POST['running_title'] ?? '');
    $category         = trim($_POST['category'] ?? 'Research Paper');
    $abstract         = trim($_POST['abstract'] ?? '');
    $keywords         = trim($_POST['keywords'] ?? '');
    $jel_codes        = trim($_POST['jel_codes'] ?? '');

    if (empty($author_name) || empty($author_surname) || empty($author_email) || empty($manuscript_title)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all mandatory fields.']);
        exit;
    }

    // Generate unique tracking ref
    $tracking_ref = 'AJFTFP-' . date('Y') . '-MS-' . str_pad(mt_rand(10, 999), 3, '0', STR_PAD_LEFT);

    // Handle File Uploads
    $file_manuscript  = '';
    $file_cover_sheet = '';
    $file_declaration = '';

    $allowed_extensions = ['doc', 'docx', 'pdf'];

    // Helper for saving uploaded file safely
    function handleUpload($fileKey, $subFolder, $prefix, $allowedExts) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        $filename = $_FILES[$fileKey]['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            throw new Exception("Invalid file type for {$fileKey}. Allowed: " . implode(', ', $allowedExts));
        }
        $targetDir = AJFTFP_UPLOADS_DIR . '/' . $subFolder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $safeName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $targetDir . '/' . $safeName;
        if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
            throw new Exception("Failed to save uploaded file {$fileKey}.");
        }
        return 'admin/uploads/' . $subFolder . '/' . $safeName;
    }

    if (!empty($_FILES['file_manuscript']['name'])) {
        $file_manuscript = handleUpload('file_manuscript', 'manuscripts', 'MS_' . $tracking_ref, ['doc', 'docx', 'pdf']);
    }
    if (!empty($_FILES['file_cover_sheet']['name'])) {
        $file_cover_sheet = handleUpload('file_cover_sheet', 'cover_sheets', 'COVER_' . $tracking_ref, ['doc', 'docx', 'pdf']);
    }
    if (!empty($_FILES['file_declaration']['name'])) {
        $file_declaration = handleUpload('file_declaration', 'declarations', 'DECL_' . $tracking_ref, ['doc', 'docx', 'pdf']);
    }

    // Insert into database
    $stmt = $db->prepare("INSERT INTO submissions (
        tracking_ref, author_title, author_name, author_surname, author_email,
        author_phone, affiliation, country, manuscript_title, running_title,
        category, abstract, keywords, jel_codes, file_manuscript,
        file_cover_sheet, file_declaration, status, created_at
    ) VALUES (
        :tracking_ref, :author_title, :author_name, :author_surname, :author_email,
        :author_phone, :affiliation, :country, :manuscript_title, :running_title,
        :category, :abstract, :keywords, :jel_codes, :file_manuscript,
        :file_cover_sheet, :file_declaration, 'Under Review', datetime('now')
    )");

    $stmt->execute([
        ':tracking_ref'     => $tracking_ref,
        ':author_title'     => $author_title,
        ':author_name'      => $author_name,
        ':author_surname'   => $author_surname,
        ':author_email'     => $author_email,
        ':author_phone'     => $author_phone,
        ':affiliation'      => $affiliation,
        ':country'          => $country,
        ':manuscript_title' => $manuscript_title,
        ':running_title'    => $running_title,
        ':category'         => $category,
        ':abstract'         => $abstract,
        ':keywords'         => $keywords,
        ':jel_codes'        => $jel_codes,
        ':file_manuscript'  => $file_manuscript,
        ':file_cover_sheet' => $file_cover_sheet,
        ':file_declaration' => $file_declaration
    ]);

    echo json_encode([
        'success'      => true,
        'tracking_ref' => $tracking_ref,
        'message'      => 'Manuscript submitted successfully!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing submission: ' . $e->getMessage()
    ]);
}
