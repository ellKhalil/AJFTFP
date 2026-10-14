<?php
/**
 * AJFTFP Editorial Admin Dashboard
 * Manage Author Submissions and Published Articles
 */
require_once __DIR__ . '/config.php';
requireAdminAuth();

$db = getDB();
$message = '';
$message_type = 'success';

// -----------------------------------------------------------------------------
// Action Handlers (Update Status, Publish Article, Delete)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Submission Status & Notes
    if ($action === 'update_submission_status') {
        $sub_id = intval($_POST['submission_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Under Review');
        $notes  = trim($_POST['reviewer_notes'] ?? '');

        if ($sub_id > 0) {
            $stmt = $db->prepare("UPDATE submissions SET status = :status, reviewer_notes = :notes WHERE id = :id");
            $stmt->execute([':status' => $status, ':notes' => $notes, ':id' => $sub_id]);
            $message = "Submission status updated successfully.";
        }
    }

    // 2. Delete Submission
    if ($action === 'delete_submission') {
        $sub_id = intval($_POST['submission_id'] ?? 0);
        if ($sub_id > 0) {
            $stmt = $db->prepare("DELETE FROM submissions WHERE id = :id");
            $stmt->execute([':id' => $sub_id]);
            $message = "Submission deleted.";
        }
    }

    // 3. Add or Publish New Article
    if ($action === 'publish_article') {
        $title          = trim($_POST['title'] ?? '');
        $authors        = trim($_POST['authors'] ?? '');
        $affiliations   = trim($_POST['affiliations'] ?? '');
        $email          = trim($_POST['corresponding_email'] ?? '');
        $abstract       = trim($_POST['abstract'] ?? '');
        $keywords       = trim($_POST['keywords'] ?? '');
        $category       = trim($_POST['category'] ?? 'Original Research');
        $volume         = intval($_POST['volume'] ?? 1);
        $issue          = intval($_POST['issue'] ?? 1);
        $year           = intval($_POST['year'] ?? 2026);
        $doi_suffix     = str_pad(mt_rand(100, 999), 4, '0', STR_PAD_LEFT);
        $doi            = trim($_POST['doi'] ?? '') ?: "10.5281/ajftfp.{$year}.{$volume}{$issue}{$doi_suffix}";
        $published_date = trim($_POST['published_date'] ?? '') ?: date('F d, Y');

        $file_docx = '';
        $file_pdf  = '';

        if (!empty($_FILES['file_docx']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_docx']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['doc', 'docx'])) {
                $safeName = 'ARTICLE_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                move_uploaded_file($_FILES['file_docx']['tmp_name'], AJFTFP_UPLOADS_DIR . '/published/' . $safeName);
                $file_docx = 'admin/uploads/published/' . $safeName;
            }
        }

        if (!empty($_FILES['file_pdf']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $safeName = 'ARTICLE_' . time() . '_' . bin2hex(random_bytes(3)) . '.pdf';
                move_uploaded_file($_FILES['file_pdf']['tmp_name'], AJFTFP_UPLOADS_DIR . '/published/' . $safeName);
                $file_pdf = 'admin/uploads/published/' . $safeName;
            }
        }

        if (!empty($title) && !empty($authors)) {
            $stmt = $db->prepare("INSERT INTO published_articles (
                doi, volume, issue, year, title, short_title, authors, affiliations,
                corresponding_email, abstract, keywords, category, file_docx, file_pdf,
                published_date, created_at
            ) VALUES (
                :doi, :volume, :issue, :year, :title, :short_title, :authors, :affiliations,
                :email, :abstract, :keywords, :category, :file_docx, :file_pdf,
                :published_date, datetime('now')
            )");
            $stmt->execute([
                ':doi'          => $doi,
                ':volume'       => $volume,
                ':issue'        => $issue,
                ':year'         => $year,
                ':title'        => $title,
                ':short_title'  => substr($title, 0, 50),
                ':authors'      => $authors,
                ':affiliations' => $affiliations,
                ':email'        => $email,
                ':abstract'     => $abstract,
                ':keywords'     => $keywords,
                ':category'     => $category,
                ':file_docx'    => $file_docx,
                ':file_pdf'     => $file_pdf,
                ':published_date' => $published_date
            ]);
            $message = "Article published to journal repository successfully!";
        }
    }

    // 4. Delete Published Article
    if ($action === 'delete_published_article') {
        $art_id = intval($_POST['article_id'] ?? 0);
        if ($art_id > 0) {
            $stmt = $db->prepare("DELETE FROM published_articles WHERE id = :id");
            $stmt->execute([':id' => $art_id]);
            $message = "Published article deleted.";
        }
    }
}

// -----------------------------------------------------------------------------
// Fetch Data
// -----------------------------------------------------------------------------
$submissions = $db->query("SELECT * FROM submissions ORDER BY id DESC")->fetchAll();
$published_articles = $db->query("SELECT * FROM published_articles ORDER BY id DESC")->fetchAll();

$count_submissions = count($submissions);
$count_under_review = 0;
$count_accepted = 0;
foreach ($submissions as $s) {
    if ($s['status'] === 'Accepted') $count_accepted++;
    if ($s['status'] === 'Under Review') $count_under_review++;
}
$count_published = count($published_articles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | AJFTFP Editorial Portal</title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --aj-navy-primary: #0a2540;
      --aj-gold-primary: #c59b27;
      --aj-bg-light: #f8fafc;
      --aj-border: #e2e8f0;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--aj-bg-light);
      color: #334155;
    }
    .admin-navbar {
      background: var(--aj-navy-primary);
      color: #ffffff;
      padding: 12px 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .admin-logo {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 2px solid var(--aj-gold-primary);
      object-fit: contain;
      background: #ffffff;
    }
    .stat-card {
      background: #ffffff;
      border: 1px solid var(--aj-border);
      border-radius: 10px;
      padding: 18px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      transition: all 0.2s ease;
    }
    .stat-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .stat-number {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--aj-navy-primary);
      margin-bottom: 2px;
    }
    .card-panel {
      background: #ffffff;
      border: 1px solid var(--aj-border);
      border-radius: 10px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .badge-status {
      font-size: 0.75rem;
      padding: 5px 10px;
      border-radius: 20px;
      font-weight: 600;
    }
    .status-Under-Review { background: #fef3c7; color: #92400e; }
    .status-Accepted { background: #dcfce7; color: #166534; }
    .status-Revision { background: #e0e7ff; color: #3730a3; }
    .status-Rejected { background: #fee2e2; color: #991b1b; }
    .status-Published { background: #dbeafe; color: #1e40af; }
    .table-responsive {
      max-height: 600px;
      overflow-y: auto;
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="admin-navbar d-flex flex-wrap justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
      <img src="../assets/img/logo.jpg" alt="Logo" class="admin-logo">
      <div>
        <h5 class="mb-0 text-white fw-bold">AJFTFP Editorial Dashboard</h5>
        <small class="text-white-50">Department of Management Sciences &bull; Federal University Dutse</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-3 mt-2 mt-md-0">
      <a href="../index.html" target="_blank" class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-up-right me-1"></i> Public Website
      </a>
      <a href="logout.php" class="btn btn-sm btn-danger">
        <i class="bi bi-box-arrow-right me-1"></i> Logout
      </a>
    </div>
  </header>

  <div class="container-fluid px-lg-4 px-xl-5 py-4">

    <?php if (!empty($message)): ?>
      <div class="alert alert-success alert-dismissible fade show py-2 small d-flex align-items-center mb-3">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-number"><?php echo $count_submissions; ?></div>
              <div class="small text-muted fw-bold">Total Submissions</div>
            </div>
            <div class="p-3 bg-light rounded text-primary fs-3"><i class="bi bi-file-earmark-arrow-up"></i></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-number text-warning"><?php echo $count_under_review; ?></div>
              <div class="small text-muted fw-bold">Under Review</div>
            </div>
            <div class="p-3 bg-light rounded text-warning fs-3"><i class="bi bi-clock-history"></i></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-number text-success"><?php echo $count_accepted; ?></div>
              <div class="small text-muted fw-bold">Accepted Papers</div>
            </div>
            <div class="p-3 bg-light rounded text-success fs-3"><i class="bi bi-check2-circle"></i></div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-number text-info"><?php echo $count_published; ?></div>
              <div class="small text-muted fw-bold">Published in Issues</div>
            </div>
            <div class="p-3 bg-light rounded text-info fs-3"><i class="bi bi-journal-bookmark-fill"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active px-3 py-2 fw-bold" id="submissions-tab" data-bs-toggle="pill" data-bs-target="#submissions-pane" type="button">
          <i class="bi bi-inbox me-1"></i> Author Submissions (<?php echo $count_submissions; ?>)
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link px-3 py-2 fw-bold" id="published-tab" data-bs-toggle="pill" data-bs-target="#published-pane" type="button">
          <i class="bi bi-journal-check me-1"></i> Published Articles (<?php echo $count_published; ?>)
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link px-3 py-2 fw-bold" id="new-article-tab" data-bs-toggle="pill" data-bs-target="#new-article-pane" type="button">
          <i class="bi bi-plus-circle me-1"></i> Publish New Article
        </button>
      </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content">
      
      <!-- TAB 1: Submissions -->
      <div class="tab-pane fade show active" id="submissions-pane">
        <div class="card-panel">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0 text-navy"><i class="bi bi-files text-primary me-2"></i>Submitted Manuscripts</h5>
            <small class="text-muted">Real-time author submissions from the online portal</small>
          </div>

          <?php if (empty($submissions)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
              <h6>No manuscripts submitted yet</h6>
              <p class="small">When authors upload manuscripts via the online submission portal, they will appear here with downloadable blinded manuscripts, cover sheets, and author details.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle small">
                <thead class="table-light">
                  <tr>
                    <th>Ref #</th>
                    <th>Author &amp; Affiliation</th>
                    <th>Manuscript Title</th>
                    <th>Category</th>
                    <th>Files</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($submissions as $sub): 
                    $safeStatus = preg_replace('/[^A-Za-z0-9\-]/', '-', $sub['status']);
                  ?>
                    <tr>
                      <td class="fw-bold text-navy"><?php echo htmlspecialchars($sub['tracking_ref']); ?></td>
                      <td>
                        <strong><?php echo htmlspecialchars($sub['author_title'] . ' ' . $sub['author_name'] . ' ' . $sub['author_surname']); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($sub['affiliation']); ?></span><br>
                        <a href="mailto:<?php echo htmlspecialchars($sub['author_email']); ?>"><?php echo htmlspecialchars($sub['author_email']); ?></a>
                      </td>
                      <td style="max-width: 280px;">
                        <strong><?php echo htmlspecialchars($sub['manuscript_title']); ?></strong>
                      </td>
                      <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($sub['category']); ?></span></td>
                      <td>
                        <div class="d-flex flex-column gap-1">
                          <?php if (!empty($sub['file_manuscript'])): ?>
                            <a href="../<?php echo htmlspecialchars($sub['file_manuscript']); ?>" download class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.75rem;">
                              <i class="bi bi-file-earmark-word me-1"></i> Blinded MS
                            </a>
                          <?php endif; ?>
                          <?php if (!empty($sub['file_cover_sheet'])): ?>
                            <a href="../<?php echo htmlspecialchars($sub['file_cover_sheet']); ?>" download class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size: 0.75rem;">
                              <i class="bi bi-file-earmark-text me-1"></i> Title Page
                            </a>
                          <?php endif; ?>
                          <?php if (!empty($sub['file_declaration'])): ?>
                            <a href="../<?php echo htmlspecialchars($sub['file_declaration']); ?>" download class="btn btn-xs btn-outline-info py-0 px-1" style="font-size: 0.75rem;">
                              <i class="bi bi-shield-check me-1"></i> Declaration
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <span class="badge-status status-<?php echo $safeStatus; ?>">
                          <?php echo htmlspecialchars($sub['status']); ?>
                        </span>
                      </td>
                      <td class="text-muted text-nowrap"><?php echo date('M d, Y', strtotime($sub['created_at'])); ?></td>
                      <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-navy py-1 px-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $sub['id']; ?>">
                          <i class="bi bi-pencil-square"></i> Review
                        </button>
                      </td>
                    </tr>

                    <!-- Edit Status Modal -->
                    <div class="modal fade" id="editModal<?php echo $sub['id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <form method="POST">
                            <input type="hidden" name="action" value="update_submission_status">
                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                            <div class="modal-header bg-light">
                              <h6 class="modal-title fw-bold">Manage Submission: <?php echo htmlspecialchars($sub['tracking_ref']); ?></h6>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body small">
                              <div class="mb-3">
                                <h6><?php echo htmlspecialchars($sub['manuscript_title']); ?></h6>
                                <p class="text-muted mb-2"><strong>Authors:</strong> <?php echo htmlspecialchars($sub['author_title'] . ' ' . $sub['author_name'] . ' ' . $sub['author_surname']); ?> (<?php echo htmlspecialchars($sub['affiliation']); ?>)</p>
                                <div class="p-2 bg-light border rounded mb-2">
                                  <strong>Abstract:</strong>
                                  <p class="mb-0 mt-1" style="text-align: justify;"><?php echo nl2br(htmlspecialchars($sub['abstract'])); ?></p>
                                </div>
                              </div>
                              <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                  <label class="form-label fw-bold">Review Status</label>
                                  <select name="status" class="form-select form-select-sm">
                                    <option value="Under Review" <?php if ($sub['status'] === 'Under Review') echo 'selected'; ?>>Under Review</option>
                                    <option value="Revision Required" <?php if ($sub['status'] === 'Revision Required') echo 'selected'; ?>>Revision Required</option>
                                    <option value="Accepted" <?php if ($sub['status'] === 'Accepted') echo 'selected'; ?>>Accepted</option>
                                    <option value="Rejected" <?php if ($sub['status'] === 'Rejected') echo 'selected'; ?>>Rejected</option>
                                    <option value="Published" <?php if ($sub['status'] === 'Published') echo 'selected'; ?>>Published</option>
                                  </select>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label fw-bold">Author Contact</label>
                                  <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($sub['author_email'] . ' | ' . $sub['author_phone']); ?>" readonly>
                                </div>
                              </div>
                              <div class="mb-3">
                                <label class="form-label fw-bold">Editorial / Reviewer Notes (Internal)</label>
                                <textarea name="reviewer_notes" class="form-control form-control-sm" rows="3" placeholder="Add reviewer comments, turnaround notes, or publication schedule..."><?php echo htmlspecialchars($sub['reviewer_notes'] ?? ''); ?></textarea>
                              </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                              <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Are you sure you want to permanently delete this submission?')) { document.getElementById('deleteForm<?php echo $sub['id']; ?>').submit(); }">
                                <i class="bi bi-trash"></i> Delete
                              </button>
                              <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                              </div>
                            </div>
                          </form>
                          <form id="deleteForm<?php echo $sub['id']; ?>" method="POST" style="display:none;">
                            <input type="hidden" name="action" value="delete_submission">
                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                          </form>
                        </div>
                      </div>
                    </div>

                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- TAB 2: Published Articles -->
      <div class="tab-pane fade" id="published-pane">
        <div class="card-panel">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0 text-navy"><i class="bi bi-journal-check text-success me-2"></i>Published Journal Issues &amp; Articles</h5>
            <button class="btn btn-sm btn-gold fw-bold" onclick="bootstrap.Tab.getInstance(document.getElementById('new-article-tab')).show();">
              <i class="bi bi-plus-circle me-1"></i> Add Article to Issue
            </button>
          </div>

          <?php if (empty($published_articles)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
              <h6>No published articles currently listed in issues</h6>
              <p class="small">When you accept manuscripts or want to publish papers to Volume 1, click "Publish New Article" above to add them.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle small">
                <thead class="table-light">
                  <tr>
                    <th>Volume / Issue</th>
                    <th>DOI</th>
                    <th>Article Title</th>
                    <th>Authors</th>
                    <th>Category</th>
                    <th>Files</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($published_articles as $art): ?>
                    <tr>
                      <td class="fw-bold text-nowrap">Vol. <?php echo htmlspecialchars($art['volume']); ?> No. <?php echo htmlspecialchars($art['issue']); ?> (<?php echo htmlspecialchars($art['year']); ?>)</td>
                      <td class="text-muted text-nowrap"><?php echo htmlspecialchars($art['doi']); ?></td>
                      <td style="max-width: 300px;"><strong><?php echo htmlspecialchars($art['title']); ?></strong></td>
                      <td><?php echo htmlspecialchars($art['authors']); ?></td>
                      <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($art['category']); ?></span></td>
                      <td>
                        <div class="d-flex gap-1">
                          <?php if (!empty($art['file_docx'])): ?>
                            <a href="../<?php echo htmlspecialchars($art['file_docx']); ?>" download class="btn btn-xs btn-outline-primary py-0 px-1" title="Download Word">
                              <i class="bi bi-file-earmark-word"></i>
                            </a>
                          <?php endif; ?>
                          <?php if (!empty($art['file_pdf'])): ?>
                            <a href="../<?php echo htmlspecialchars($art['file_pdf']); ?>" download class="btn btn-xs btn-outline-danger py-0 px-1" title="Download PDF">
                              <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="text-nowrap"><?php echo htmlspecialchars($art['published_date']); ?></td>
                      <td class="text-end text-nowrap">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this published article from repository?');">
                          <input type="hidden" name="action" value="delete_published_article">
                          <input type="hidden" name="article_id" value="<?php echo $art['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2">
                            <i class="bi bi-trash"></i> Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- TAB 3: Publish New Article -->
      <div class="tab-pane fade" id="new-article-pane">
        <div class="card-panel">
          <h5 class="fw-bold mb-3 text-navy"><i class="bi bi-plus-circle-fill text-warning me-2"></i>Publish Article to Volume / Issue</h5>
          <p class="small text-muted mb-4">Add accepted research papers to the official journal repository with DOIs, metadata, and galleys.</p>

          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="publish_article">

            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold">Volume</label>
                <input type="number" name="volume" class="form-control form-control-sm" value="1" required>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Issue</label>
                <input type="number" name="issue" class="form-control form-control-sm" value="1" required>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Year</label>
                <input type="number" name="year" class="form-control form-control-sm" value="2026" required>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold">Category</label>
                <select name="category" class="form-select form-select-sm">
                  <option value="Original Research Article">Original Research Article</option>
                  <option value="Review Article">Review Article</option>
                  <option value="Policy Analysis">Policy Analysis</option>
                  <option value="Case Study">Case Study</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold">Article Title *</label>
                <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Audit Quality, Corporate Investments Structure, and Corporate Tax Avoidance" required>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Author(s) *</label>
                <input type="text" name="authors" class="form-control form-control-sm" placeholder="e.g. Dr. Mohammed Auwal Babangida &amp; Prof. Nasiru Musa Yauri" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Affiliation(s)</label>
                <input type="text" name="affiliations" class="form-control form-control-sm" placeholder="e.g. Department of Taxation, Federal University Dutse">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Corresponding Email</label>
                <input type="email" name="corresponding_email" class="form-control form-control-sm" placeholder="author@fud.edu.ng">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Digital Object Identifier (DOI)</label>
                <input type="text" name="doi" class="form-control form-control-sm" placeholder="e.g. 10.5281/ajftfp.2026.0101 (Leave blank to autogenerate)">
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold">Abstract *</label>
                <textarea name="abstract" class="form-control form-control-sm" rows="4" placeholder="Enter full structured abstract..." required></textarea>
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold">Keywords</label>
                <input type="text" name="keywords" class="form-control form-control-sm" placeholder="e.g. Tax Compliance, Corporate Governance, Financial Reporting">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold">Upload Manuscript Document (.DOCX / .DOC)</label>
                <input type="file" name="file_docx" class="form-control form-control-sm" accept=".doc,.docx">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Upload Published PDF (.PDF)</label>
                <input type="file" name="file_pdf" class="form-control form-control-sm" accept=".pdf">
              </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
              <button type="reset" class="btn btn-sm btn-outline-secondary">Reset Form</button>
              <button type="submit" class="btn btn-sm btn-primary fw-bold px-4">
                <i class="bi bi-check2-circle me-1"></i> Publish Article
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
