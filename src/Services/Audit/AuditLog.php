<?php
declare(strict_types=1);

namespace Carlesso\Services\Audit;

use Carlesso\Support\Database;

/**
 * AuditLog — registra ações sensíveis na tabela audit_log.
 *
 * Falhas silenciosas: se o log falhar (ex: tabela não existe), não derruba a operação.
 *
 * Uso típico:
 *   AuditLog::record('user.created', 'user', $newUserId, ['email' => $email]);
 *   AuditLog::record('post.published', 'post', $postId);
 *   AuditLog::record('permissions.updated', 'role', null, ['role' => 'editor', 'added' => [...], 'removed' => [...]]);
 */
final class AuditLog
{
    public static function record(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $meta = null
    ): void {
        try {
            $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            Database::insert('audit_log', [
                'user_id'     => $userId,
                'action'      => substr($action, 0, 50),
                'target_type' => $targetType ? substr($targetType, 0, 40) : null,
                'target_id'   => $targetId,
                'meta'        => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            error_log('[AuditLog] ' . $e->getMessage());
        }
    }

    /**
     * Lista os últimos N eventos para exibir no admin (Phase 4 surfacing).
     */
    public static function recent(int $limit = 50): array
    {
        try {
            return Database::fetchAll(
                'SELECT a.*, u.username, u.full_name
                 FROM audit_log a
                 LEFT JOIN usuarios u ON u.id = a.user_id
                 ORDER BY a.created_at DESC
                 LIMIT ' . max(1, min($limit, 500))
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
