<?php

require_once __DIR__ . '/config.php';

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Không thể kết nối tới cơ sở dữ liệu: ' . $e->getMessage(), 0, $e);
        }
    }

    return $pdo;
}


function getAvailableProductTags(PDO $pdo): array
{
    $tags = [];
    
    // First, get tags from product_tags_meta (primary source)
    try {
        $metaStmt = $pdo->query('SELECT name FROM product_tags_meta ORDER BY name ASC');
        $metaTags = $metaStmt->fetchAll(PDO::FETCH_COLUMN);
        $tags = array_filter($metaTags, static fn($tag) => $tag !== null && $tag !== '');
    } catch (Exception $e) {
        $tags = [];
    }
    
    // Then, get tags from product_tags that don't exist in product_tags_meta (legacy tags)
    try {
        $relationStmt = $pdo->query('
            SELECT DISTINCT pt.tag 
            FROM product_tags pt
            LEFT JOIN product_tags_meta ptm ON ptm.name COLLATE utf8mb4_unicode_ci = pt.tag COLLATE utf8mb4_unicode_ci
            WHERE ptm.tag_id IS NULL
            ORDER BY pt.tag ASC
        ');
        $relationTags = $relationStmt->fetchAll(PDO::FETCH_COLUMN);
        $legacyTags = array_filter($relationTags, static fn($tag) => $tag !== null && $tag !== '');
        $tags = array_merge($tags, $legacyTags);
    } catch (Exception $e) {
        // Ignore if product_tags doesn't exist or query fails
    }
    
    $tags = array_values(array_unique($tags));
    natcasesort($tags);
    return array_values($tags);
}
