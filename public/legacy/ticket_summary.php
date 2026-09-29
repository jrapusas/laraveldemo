<?php

/**
 * Legacy-style reporting endpoint (vanilla PHP 8).
 * Uses the same database as Laravel (.env) — mirrors "maintain vanilla PHP" JD theme.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__.'/db.php';

try {
    $pdo = legacy_pdo();
} catch (Throwable $e) {
    legacy_json_error($e, 'legacy/ticket_summary.php');
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 100);
$perPage = min(100, max(10, $perPage));

$total = (int) $pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
$lastPage = max(1, (int) ceil($total / $perPage));
if ($page > $lastPage) {
    $page = $lastPage;
}
$offset = ($page - 1) * $perPage;

$sql = <<<'SQL'
SELECT t.reference,
       t.type,
       t.priority,
       t.status,
       t.subject,
       a.name AS account_name,
       a.tier AS account_tier
FROM tickets t
INNER JOIN accounts a ON a.id = t.account_id
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset
SQL;

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'generated_by' => 'legacy/ticket_summary.php',
    'open_queue' => $rows,
    'count' => count($rows),
    'total' => $total,
    'current_page' => $page,
    'last_page' => $lastPage,
    'per_page' => $perPage,
], JSON_PRETTY_PRINT);
