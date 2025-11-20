<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require_once __DIR__ . '/../../includes/database.php';


function adminSlugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }


    if (function_exists('transliterator_transliterate')) {
        $converted = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
        if ($converted !== false && $converted !== null) {
            $text = $converted;
        }
    } elseif (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false && $converted !== null) {
            $text = $converted;
        }
    }


    $map = [
        'đ' => 'd', 'Đ' => 'd'
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
    $text = preg_replace('/[\s-]+/', '-', $text) ?? '';


    return trim($text, '-');
}


function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);


    return (bool)$stmt->fetchColumn();
}


function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $stmt->execute([':table' => $table]);


    return (bool)$stmt->fetchColumn();
}


function loadProductTags(PDO $pdo, array $schemaStatus, bool $productTagsTableExists, ?string &$error = null): array
{
    $error = null;
    $createdAtColumnExists = $schemaStatus['created_at'] ?? false;
    $slugColumnExists = $schemaStatus['slug'] ?? false;
    $descriptionColumnExists = $schemaStatus['description'] ?? false;
    $selectCreatedAt = $createdAtColumnExists ? 't.created_at' : 'NULL';
    $orderByColumn = $createdAtColumnExists ? 't.created_at DESC' : 't.tag_id DESC';
    $selectSlug = $slugColumnExists ? 't.slug' : 'NULL';
    $selectDescription = $descriptionColumnExists ? 'IFNULL(t.description, "")' : '""';


    $usageSelect = '0 AS usage_count';
    $usageJoin = '';
    if ($productTagsTableExists) {
        $usageSelect = 'COALESCE(pt.usage_count, 0) AS usage_count';
        $usageJoin = '
            LEFT JOIN (
                SELECT tag COLLATE utf8mb4_unicode_ci AS normalized_tag, COUNT(*) AS usage_count
                FROM product_tags
                GROUP BY normalized_tag
            ) pt ON pt.normalized_tag = t.name COLLATE utf8mb4_unicode_ci';
    }


    $sql = 'SELECT t.tag_id, t.name, ' . $selectSlug . ' AS slug, ' . $selectDescription . ' AS description,
                   ' . $usageSelect . ', ' . $selectCreatedAt . ' AS created_at
            FROM product_tags_meta t' . $usageJoin . '
            ORDER BY ' . $orderByColumn;


    try {
        $tagsStmt = $pdo->query($sql);
        $tags = $tagsStmt ? $tagsStmt->fetchAll(PDO::FETCH_ASSOC) : [];


        foreach ($tags as &$tagRow) {
            if (empty($tagRow['slug'])) {
                $tagRow['slug'] = adminSlugify($tagRow['name']);
            }
            if (!isset($tagRow['description'])) {
                $tagRow['description'] = '';
            }
        }
        unset($tagRow);


        if ($productTagsTableExists) {
            try {
                $missingStmt = $pdo->query(
                    'SELECT pt.tag COLLATE utf8mb4_unicode_ci AS name, COUNT(pt.tag) AS usage_count
                     FROM product_tags pt
                     LEFT JOIN product_tags_meta tm ON tm.name COLLATE utf8mb4_unicode_ci = pt.tag COLLATE utf8mb4_unicode_ci
                     WHERE tm.tag_id IS NULL
                     GROUP BY name
                     ORDER BY name ASC'
                );


                if ($missingStmt) {
                    $missing = $missingStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($missing as $row) {
                        $tags[] = [
                            'tag_id' => null,
                            'name' => $row['name'],
                            'slug' => adminSlugify($row['name']),
                            'description' => '',
                            'usage_count' => (int)$row['usage_count'],
                            'created_at' => null,
                        ];
                    }
                }
            } catch (Throwable $fallbackException) {
                $error .= ' | Fallback join: ' . $fallbackException->getMessage();
                error_log('Fallback loadProductTags join failed: ' . $fallbackException->getMessage());
            }
        }


        return $tags;
    } catch (Throwable $primaryException) {
        $error = $primaryException->getMessage();
        error_log('Failed to load product tags: ' . $primaryException->getMessage());
    }


    try {
        $simpleStmt = $pdo->query('SELECT tag_id, name FROM product_tags_meta ORDER BY tag_id DESC');
        if ($simpleStmt) {
            $raw = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
            $usageMap = [];


            if ($productTagsTableExists) {
                try {
                    $usageStmt = $pdo->query('SELECT tag COLLATE utf8mb4_unicode_ci AS normalized_tag, COUNT(*) AS usage_count FROM product_tags GROUP BY normalized_tag');
                    if ($usageStmt) {
                        foreach ($usageStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            $usageMap[$row['normalized_tag']] = (int)$row['usage_count'];
                        }
                    }
                } catch (Throwable $usageException) {
                    $error .= ' | Usage map: ' . $usageException->getMessage();
                    error_log('Failed to load product tag usage counts: ' . $usageException->getMessage());
                }
            }


            $fallbackTags = [];
            foreach ($raw as $row) {
                $name = $row['name'];
                $fallbackTags[] = [
                    'tag_id' => (int)$row['tag_id'],
                    'name' => $name,
                    'slug' => adminSlugify($name),
                    'description' => '',
                    'usage_count' => $usageMap[$name] ?? 0,
                    'created_at' => null,
                ];
            }


            if (!empty($fallbackTags)) {
                return $fallbackTags;
            }
        }
    } catch (Throwable $simpleException) {
        $error .= ' | Simple fallback: ' . $simpleException->getMessage();
        error_log('Simple fallback loadProductTags failed: ' . $simpleException->getMessage());
    }


    if ($productTagsTableExists) {
        try {
            $fallbackStmt = $pdo->query(
                'SELECT tag COLLATE utf8mb4_unicode_ci AS name, COUNT(*) AS usage_count
                 FROM product_tags
                 GROUP BY name
                 ORDER BY name ASC'
            );
            if ($fallbackStmt) {
                $tags = [];
                foreach ($fallbackStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $tags[] = [
                        'tag_id' => null,
                        'name' => $row['name'],
                        'slug' => adminSlugify($row['name']),
                        'description' => '',
                        'usage_count' => (int)$row['usage_count'],
                        'created_at' => null,
                    ];
                }
                if (!empty($tags)) {
                    $error = null;
                }
                return $tags;
            }
        } catch (Throwable $fallbackException) {
            $error = ($error !== null ? $error . ' | ' : '') . $fallbackException->getMessage();
            error_log('Fallback loadProductTags failed: ' . $fallbackException->getMessage());
        }
    }


    return [];
}


function ensureProductTagMetaSchema(PDO $pdo): array
{
    $status = [
        'created_at' => false,
        'slug' => false,
        'description' => false,
    ];


    // Ensure slug column exists and populate missing slugs
    try {
        $slugExists = tableHasColumn($pdo, 'product_tags_meta', 'slug');
    } catch (Exception $e) {
        $slugExists = null;
    }

    if ($slugExists === false) {
        try {
            $pdo->exec('ALTER TABLE product_tags_meta ADD COLUMN slug VARCHAR(150) DEFAULT NULL AFTER name');
        } catch (Exception $e) {
            // Ignore - if this fails we'll attempt to continue with current schema
        }
    }

    try {
        $existingSlugStmt = $pdo->query('SELECT slug FROM product_tags_meta WHERE slug IS NOT NULL AND slug <> ""');
        $usedSlugs = [];
        if ($existingSlugStmt) {
            foreach ($existingSlugStmt->fetchAll(PDO::FETCH_COLUMN) as $existingSlug) {
                $usedSlugs[$existingSlug] = true;
            }
        }

        $missingSlugStmt = $pdo->query('SELECT tag_id, name FROM product_tags_meta WHERE slug IS NULL OR slug = ""');
        $rows = $missingSlugStmt ? $missingSlugStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (!empty($rows)) {
            $updateSlug = $pdo->prepare('UPDATE product_tags_meta SET slug = :slug WHERE tag_id = :id');
            foreach ($rows as $row) {
                $base = adminSlugify($row['name']);
                if ($base === '') {
                    $base = 'tag-' . (int)$row['tag_id'];
                }

                $slug = $base;
                $suffix = 2;
                while (isset($usedSlugs[$slug])) {
                    $slug = $base . '-' . $suffix;
                    $suffix++;
                }

                $updateSlug->execute([
                    ':slug' => $slug,
                    ':id' => (int)$row['tag_id'],
                ]);
                $usedSlugs[$slug] = true;
            }
        }
    } catch (Exception $e) {
        // If we cannot ensure slug uniqueness, the UI will still work with nullable slugs
    }


    try {
        $status['slug'] = tableHasColumn($pdo, 'product_tags_meta', 'slug');
    } catch (Exception $e) {
        $status['slug'] = false;
    }


    // Ensure description column exists
    try {
        if (!tableHasColumn($pdo, 'product_tags_meta', 'description')) {
            $pdo->exec('ALTER TABLE product_tags_meta ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER slug');
        }
    } catch (Exception $e) {
        // Ignore - description is optional for display
    }


    try {
        $status['description'] = tableHasColumn($pdo, 'product_tags_meta', 'description');
    } catch (Exception $e) {
        $status['description'] = false;
    }


    // Ensure created_at column exists and return its availability
    try {
        $createdColumnExists = tableHasColumn($pdo, 'product_tags_meta', 'created_at');
        if (!$createdColumnExists) {
            $pdo->exec('ALTER TABLE product_tags_meta ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            $createdColumnExists = true;
        }
        $status['created_at'] = $createdColumnExists;
    } catch (Exception $e) {
        $status['created_at'] = false;
    }


    return $status;
}


$tagName = '';
$slug = '';
$description = '';
$errors = [];
$successMessage = '';
$createdAtColumnExists = false;
$productTagsTableExists = false;
$editingTagId = null;


try {
    $pdo = getPDO();
    
    // Create product_tags_meta table if not exists
    if (!tableExists($pdo, 'product_tags_meta')) {
        $pdo->exec('CREATE TABLE product_tags_meta (
            tag_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            slug VARCHAR(150) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    // Create product_tags table if not exists
    if (!tableExists($pdo, 'product_tags')) {
        $pdo->exec('CREATE TABLE product_tags (
            product_id INT NOT NULL,
            tag VARCHAR(150) NOT NULL,
            PRIMARY KEY (product_id, tag),
            INDEX idx_product_tags_tag (tag)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    $schemaStatus = ensureProductTagMetaSchema($pdo);
    $createdAtColumnExists = $schemaStatus['created_at'] ?? false;

    try {
        $productTagsTableExists = tableExists($pdo, 'product_tags');
    } catch (Exception $innerException) {
        $productTagsTableExists = false;
    }

    // Migrate existing tags from product_tags to product_tags_meta
    if ($productTagsTableExists) {
        try {
            $existingTagsStmt = $pdo->query('SELECT DISTINCT tag FROM product_tags ORDER BY tag ASC');
            $existingTags = $existingTagsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($existingTags)) {
                $insertStmt = $pdo->prepare('INSERT IGNORE INTO product_tags_meta (name, slug) VALUES (:name, :slug)');
                foreach ($existingTags as $tagName) {
                    $slug = adminSlugify($tagName);
                    if ($slug === '') {
                        $slug = 'tag-' . md5($tagName);
                    }
                    try {
                        $insertStmt->execute([':name' => $tagName, ':slug' => $slug]);
                    } catch (Exception $e) {
                        // Ignore duplicates
                    }
                }
            }
        } catch (Exception $migrationException) {
            // Log but don't fail
            error_log('Tag migration failed: ' . $migrationException->getMessage());
        }
    }
} catch (Exception $e) {
    $pdo = null;
    $errors[] = 'Không thể kết nối cơ sở dữ liệu';
}


if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';


    if ($action === 'delete') {
        $tagId = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
        if ($tagId <= 0) {
            $errors[] = 'Tag không hợp lệ để xóa.';
        } else {
            try {
                $pdo->beginTransaction();


                $metaStmt = $pdo->prepare('SELECT tag_id, name FROM product_tags_meta WHERE tag_id = :id');
                $metaStmt->execute([':id' => $tagId]);
                $tagRow = $metaStmt->fetch();


                if (!$tagRow) {
                    $pdo->rollBack();
                    $errors[] = 'Tag không tồn tại hoặc đã bị xóa.';
                } else {
                    $deleteRelations = $pdo->prepare('DELETE FROM product_tags WHERE tag COLLATE utf8mb4_unicode_ci = :tag');
                    $deleteRelations->execute([':tag' => $tagRow['name']]);


                    $deleteMeta = $pdo->prepare('DELETE FROM product_tags_meta WHERE tag_id = :id');
                    $deleteMeta->execute([':id' => $tagId]);


                    $pdo->commit();
                    $successMessage = 'Đã xóa tag "' . $tagRow['name'] . '" thành công.';
                    $tagName = '';
                    $slug = '';
                    $description = '';
                    $editingTagId = null;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Không thể xóa tag. Vui lòng thử lại.';
            }
        }
    } else {
        $tagName = trim($_POST['tag_name'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $slug = $slugInput !== '' ? adminSlugify($slugInput) : adminSlugify($tagName);

        if ($tagName === '') {
            $errors[] = 'Vui lòng nhập tên tag';
        }
        if ($slug === '') {
            $errors[] = 'Không thể tạo slug hợp lệ';
        }


        if (empty($errors)) {
            if ($action === 'update') {
                $editingTagId = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
                if ($editingTagId <= 0) {
                    $errors[] = 'Tag không hợp lệ để cập nhật.';
                }
            }
        }


        if (empty($errors)) {
            try {
                if ($action === 'update') {
                    $existsStmt = $pdo->prepare('SELECT tag_id FROM product_tags_meta WHERE tag_id = :id');
                    $existsStmt->execute([':id' => $editingTagId]);
                    $existingId = $existsStmt->fetchColumn();
                    if (!$existingId) {
                        $errors[] = 'Tag cần sửa không tồn tại.';
                    } else {
                        $oldRowStmt = $pdo->prepare('SELECT name FROM product_tags_meta WHERE tag_id = :id');
                        $oldRowStmt->execute([':id' => $editingTagId]);
                        $existingRow = $oldRowStmt->fetch();
                        if (!$existingRow) {
                            $errors[] = 'Tag cần sửa không tồn tại.';
                        }
                    }
                }


                if (empty($errors)) {
                    $params = [
                        ':name' => $tagName,
                        ':slug' => $slug,
                    ];


                    if ($action === 'update') {
                        $checkName = $pdo->prepare('SELECT 1 FROM product_tags_meta WHERE name = :name AND tag_id <> :id');
                        $checkName->execute([':name' => $tagName, ':id' => $editingTagId]);
                        $checkSlug = $pdo->prepare('SELECT 1 FROM product_tags_meta WHERE slug = :slug AND slug IS NOT NULL AND tag_id <> :id');
                        $checkSlug->execute([':slug' => $slug, ':id' => $editingTagId]);
                    } else {
                        $checkName = $pdo->prepare('SELECT 1 FROM product_tags_meta WHERE name = :name');
                        $checkName->execute([':name' => $tagName]);
                        $checkSlug = $pdo->prepare('SELECT 1 FROM product_tags_meta WHERE slug = :slug AND slug IS NOT NULL');
                        $checkSlug->execute([':slug' => $slug]);
                    }

                    if ($checkName->fetchColumn()) {
                        $errors[] = 'Tag đã tồn tại, vui lòng nhập tên khác';
                    } elseif ($checkSlug->fetchColumn()) {
                        $errors[] = 'Slug đã tồn tại, vui lòng chọn slug khác';
                    } else {
                        if ($action === 'update') {
                            try {
                                $pdo->beginTransaction();
                                $update = $pdo->prepare('UPDATE product_tags_meta SET name = :name, slug = :slug, description = :description WHERE tag_id = :id');
                                $update->execute([
                                    ':name' => $tagName,
                                    ':slug' => $slug,
                                    ':description' => $description !== '' ? $description : null,
                                    ':id' => $editingTagId,
                                ]);


                                if (!empty($existingRow['name']) && $existingRow['name'] !== $tagName) {
                                    $renameRelations = $pdo->prepare('UPDATE product_tags SET tag = :newName WHERE tag COLLATE utf8mb4_unicode_ci = :oldName');
                                    $renameRelations->execute([
                                        ':newName' => $tagName,
                                        ':oldName' => $existingRow['name'],
                                    ]);
                                }


                                $pdo->commit();
                            } catch (Exception $txnException) {
                                if ($pdo->inTransaction()) {
                                    $pdo->rollBack();
                                }
                                throw $txnException;
                            }


                            $successMessage = 'Đã cập nhật tag thành công.';
                            $editingTagId = null;
                            $tagName = '';
                            $slug = '';
                            $description = '';
                        } else {
                            try {
                                $pdo->beginTransaction();
                                
                                $insert = $pdo->prepare('INSERT INTO product_tags_meta (name, slug, description) VALUES (:name, :slug, :description)');
                                $insert->execute([
                                    ':name' => $tagName,
                                    ':slug' => $slug,
                                    ':description' => $description !== '' ? $description : null
                                ]);
                                
                                $newTagId = (int)$pdo->lastInsertId();
                                
                                $pdo->commit();
                                $successMessage = 'Đã thêm tag mới thành công';
                                $tagName = '';
                                $slug = '';
                                $description = '';
                            } catch (Exception $insertException) {
                                if ($pdo->inTransaction()) {
                                    $pdo->rollBack();
                                }
                                throw $insertException;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = $action === 'update' ? 'Không thể cập nhật tag.' : 'Không thể lưu tag mới';
            }
        }
    }
}


if ($pdo && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editingTagId = (int)$_GET['edit'];
    if ($editingTagId > 0) {
        try {
            $editStmt = $pdo->prepare('SELECT tag_id, name, slug, IFNULL(description, "") AS description FROM product_tags_meta WHERE tag_id = :id');
            $editStmt->execute([':id' => $editingTagId]);
            $editRow = $editStmt->fetch();
            if ($editRow) {
                $tagName = $editRow['name'];
                $slug = $editRow['slug'];
                $description = $editRow['description'];
            } else {
                $editingTagId = null;
            }
        } catch (Exception $e) {
            $editingTagId = null;
        }
    } else {
        $editingTagId = null;
    }
}


$tags = [];
$tagLoadError = null;
if ($pdo) {
    $tags = loadProductTags($pdo, $schemaStatus, $productTagsTableExists, $tagLoadError);
    if ($tagLoadError !== null) {
        $errors[] = 'Không thể tải danh sách tag, vui lòng thử lại. (' . htmlspecialchars($tagLoadError) . ')';
    }
}


$totalTags = count($tags);
$totalProductTagRelations = 0;
foreach ($tags as $info) {
    $totalProductTagRelations += (int)$info['usage_count'];
}


include __DIR__ . '/../includes/header.php';
?>


<style>
    .tags-layout { max-width: <?php echo CONTAINER_MAX_WIDTH; ?>; margin: 20px auto; padding: 0 <?php echo CONTAINER_PADDING; ?>; display: grid; grid-template-columns: 260px 1fr; gap: <?php echo GRID_GAP; ?>; }
    @media (max-width: <?php echo BREAKPOINT_MD; ?>) { .tags-layout { grid-template-columns: 1fr; } }


    .tags-content { background-color: var(--white); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
    .tags-header { padding: 20px <?php echo CONTAINER_PADDING; ?>; border-bottom: 1px solid rgba(0,0,0,0.05); background: linear-gradient(120deg, rgba(255, 239, 211, 0.4), rgba(255,255,255,1)); }
    .tags-header h1 { font-size: 24px; color: var(--primary); font-weight: 700; }
    .tags-header .breadcrumb { margin-top: 6px; color: #777; font-size: 13px; }
    .tags-header .breadcrumb a { color: var(--secondary); font-weight: 600; text-decoration: none; }
    .tags-header .breadcrumb a:hover { color: var(--primary); text-decoration: underline; }
    .tags-header .breadcrumb span { color: #777; font-weight: 500; }


    .tags-body { padding: 22px <?php echo CONTAINER_PADDING; ?> 28px; display: flex; flex-direction: column; gap: 22px; }


    .card { background: #fff; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 12px 24px rgba(0,0,0,0.04); }
    .card-header { padding: 18px 20px 0; display:flex; align-items:center; justify-content: space-between; }
    .card-header h3 { font-size: 16px; font-weight: 700; color: var(--primary); display:flex; align-items:center; gap:10px; }
    .card-header h3 i { color: var(--secondary); }
    .card-body { padding: 18px 20px 22px; }


    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 18px; }
    .stats-item { background: rgba(255, 215, 160, 0.25); border: 1px solid rgba(255, 215, 160, 0.6); border-radius: 12px; padding: 12px 14px; display:flex; flex-direction:column; gap:4px; color: #8a6118; font-weight:600; }
    .stats-item span { font-size:26px; font-weight:700; color: var(--primary); }


    .form-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
    .form-field label { font-size:12px; color:#666; text-transform:uppercase; letter-spacing:0.05em; }
    .form-field input, .form-field textarea { padding:11px 14px; border:1px solid #e0e0e0; border-radius:10px; outline:none; background:#fff; transition: border-color .2s ease, box-shadow .2s ease; }
    .form-field input:focus, .form-field textarea:focus { border-color: var(--primary); box-shadow:0 0 0 3px rgba(231, 181, 81, 0.2); }
    .form-field textarea { min-height:110px; resize:vertical; }


    .alert { border-radius: 12px; padding: 12px 14px; margin-bottom: 14px; font-size: 14px; }
    .alert-error { border:1px solid #f0c7c7; background:#fff2f0; color:#c74343; }
    .alert-success { border:1px solid #b7eb8f; background:#f6ffed; color:#1f5421; }


    .form-actions { display:flex; justify-content:flex-end; }
    .btn-primary { display:inline-flex; align-items:center; gap:8px; padding: 11px 18px; border:none; border-radius:10px; background: linear-gradient(135deg, var(--secondary), var(--primary)); color:#fff; font-weight:600; cursor:pointer; box-shadow: 0 6px 14px rgba(50,115,69,0.25); transition: transform .2s ease; }
    .btn-primary:hover { transform: translateY(-1px); }


    .table-wrapper { overflow:auto; border-radius: 14px; border:1px solid rgba(0,0,0,0.05); }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th, td { padding:14px 16px; border-bottom:1px solid rgba(0,0,0,0.05); text-align:left; }
    th { background:#fff9ef; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#8a6118; }
    .tag-name { font-weight:600; font-size:15px; color: var(--primary); }
    .slug { font-weight:600; color: #e07a19; }
    .usage-badge { display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:999px; background:rgba(28,117,63,0.12); color:#1c753f; font-weight:600; }
    .actions { display:flex; gap:10px; }
    .icon-btn { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; border:1px solid rgba(0,0,0,0.08); background:#fff; color: var(--primary); transition: background .2s ease, color .2s ease; }
    .icon-btn:hover { background:rgba(231, 181, 81, 0.18); color: var(--secondary); }


    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .tags-body { grid-template-columns:1fr; }
        th, td { padding:12px; }
    }


    @media (max-width: <?php echo BREAKPOINT_SM; ?>) {
        table, thead, tbody, th, td, tr { display:block; }
        thead { display:none; }
        tr { margin-bottom:14px; border:1px solid rgba(0,0,0,0.05); border-radius:12px; padding:12px; }
        td { border:none; padding:8px 0; }
        td::before { content: attr(data-label); display:block; font-size:11px; text-transform:uppercase; color:#888; margin-bottom:2px; }
        .actions { justify-content:flex-start; }
    }
</style>


<div class="tags-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="tags-content">
        <div class="tags-header">
            <h1>Quản Lý Tags Sản Phẩm</h1>
            <div class="breadcrumb"><a href="<?php echo BASE_URL; ?>/admin/index.php">Dashboard</a> / <a href="<?php echo BASE_URL; ?>/admin/products/index.php">Quản lý sản phẩm</a> / <span>Tags</span></div>
        </div>
        <div class="tags-body">
            <div class="card" style="background:#fff9ef;">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Thêm Tag Mới</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                        </div>
                    <?php elseif ($successMessage !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    <form method="post" action="" id="tag-form">
                        <input type="hidden" name="action" value="<?php echo $editingTagId ? 'update' : 'create'; ?>">
                        <?php if ($editingTagId): ?>
                            <input type="hidden" name="tag_id" value="<?php echo (int)$editingTagId; ?>">
                            <div class="alert alert-success" style="background:#fffbea; border-color:#ffe58f; color:#a36f00;">
                                Đang chỉnh sửa tag ID #<?php echo (int)$editingTagId; ?>. <a href="<?php echo htmlspecialchars(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>" style="color: var(--primary); font-weight:600;">Hủy chỉnh sửa</a>
                            </div>
                        <?php endif; ?>
                        <div class="form-field">
                            <label>Tên Tag *</label>
                            <input id="tag-name" type="text" name="tag_name" placeholder="VD: Cao cấp, Thiên nhiên..." value="<?php echo htmlspecialchars($tagName); ?>" />
                        </div>
                        <div class="form-field">
                            <label>Slug</label>
                            <input id="tag-slug" type="text" name="slug" placeholder="Nếu để trống sẽ tự tạo" value="<?php echo htmlspecialchars($slug); ?>" />
                        </div>
                        <div class="form-field">
                            <label>Mô tả (tùy chọn)</label>
                            <textarea name="description" placeholder="Ghi chú giúp quản trị viên dễ quản lý."><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> <?php echo $editingTagId ? 'Cập nhật Tag' : 'Thêm Tag'; ?></button>
                        </div>
                    </form>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tags"></i> Danh Sách Tags</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stats-item">
                            Tổng số tag
                            <span><?php echo $totalTags; ?></span>
                        </div>
                        <div class="stats-item" style="background:rgba(198, 235, 197, 0.4); border-color:rgba(198,235,197,0.8); color:#2f7a35;">
                            Liên kết sản phẩm
                            <span><?php echo $totalProductTagRelations; ?></span>
                        </div>
                    </div>


                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tên tag</th>
                                    <th>Slug</th>
                                    <th>Mô tả</th>
                                    <th>Sử dụng</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tags)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:#777; padding:24px;">Chưa có tag nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <tr>
                                            <td data-label="Tên tag"><span class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></span></td>
                                            <td data-label="Slug"><span class="slug"><?php echo htmlspecialchars($tag['slug']); ?></span></td>
                                            <td data-label="Mô tả">
                                                <?php if ($tag['description'] !== ''): ?>
                                                    <?php echo htmlspecialchars($tag['description']); ?>
                                                <?php else: ?>
                                                    <span style="color:#aaa;">Không có</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Sử dụng"><span class="usage-badge"><?php echo (int)$tag['usage_count']; ?> sản phẩm</span></td>
                                            <td data-label="Thao tác">
                                                <div class="actions">
                                                    <a class="icon-btn js-edit-tag" data-tag-id="<?php echo (int)$tag['tag_id']; ?>" href="#" title="Sửa"><i class="fas fa-edit"></i></a>
                                                    <a class="icon-btn js-delete-tag" data-tag-id="<?php echo (int)$tag['tag_id']; ?>" data-tag-name="<?php echo htmlspecialchars($tag['name']); ?>" href="#" title="Xóa"><i class="fas fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>


<script>
    (function () {
        const nameInput = document.getElementById('tag-name');
        const slugInput = document.getElementById('tag-slug');
        const tagForm = document.getElementById('tag-form');
        const editButtons = document.querySelectorAll('.js-edit-tag');
        const deleteButtons = document.querySelectorAll('.js-delete-tag');


        if (!nameInput || !slugInput || !tagForm) {
            return;
        }


        const slugify = (value) => {
            if (!value) {
                return '';
            }


            let text = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            text = text.replace(/[đĐ]/g, 'd');
            text = text.toLowerCase();
            text = text.replace(/[^a-z0-9\s-]/g, '');
            text = text.trim().replace(/[\s-]+/g, '-');
            return text;
        };


        const updateSlug = () => {
            if (document.activeElement === slugInput && slugInput.value !== '') {
                return;
            }


            const generated = slugify(nameInput.value);
            if (slugInput.value === '' || slugInput.dataset.autoFilled === 'true') {
                slugInput.value = generated;
                slugInput.dataset.autoFilled = 'true';
            }
        };


        const handleSlugInput = () => {
            slugInput.dataset.autoFilled = slugInput.value === '' ? 'true' : 'false';
        };


        nameInput.addEventListener('input', updateSlug);
        slugInput.addEventListener('input', handleSlugInput);


        if (nameInput.value !== '') {
            slugInput.dataset.autoFilled = 'false';
        } else {
            updateSlug();
        }


        editButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const id = btn.getAttribute('data-tag-id');
                if (!id) {
                    return;
                }


                const url = new URL(window.location.href);
                url.searchParams.set('edit', id);
                window.location.href = url.toString();
            });
        });


        deleteButtons.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const id = btn.getAttribute('data-tag-id');
                const tagName = btn.getAttribute('data-tag-name') || 'tag này';


                if (!id) {
                    return;
                }


                if (!confirm(`Bạn chắc chắn muốn xóa "${tagName}"?`)) {
                    return;
                }


                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';


                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);


                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'tag_id';
                idInput.value = id;
                form.appendChild(idInput);


                document.body.appendChild(form);
                form.submit();
            });
        });
    })();
</script>



