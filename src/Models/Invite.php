<?php
/**
 * Invite model – time-limited group join link management.
 *
 * Group invite tokens let a group owner share a single link that allows any
 * authenticated user to join their group without admin intervention.
 *
 * Token characteristics:
 *   - Format:   32-char lowercase hex (bin2hex of 16 random bytes)
 *   - Lifetime: 24 hours from creation
 *   - Scope:    one specific group
 *   - Reuse:    any number of users can use the same token within the 24h window
 *
 * Distinct from AppInvite:
 *   AppInvite codes gate registration (single-use, no expiry).
 *   Invite tokens gate group membership (multi-use, 24h expiry).
 *
 * A group may have multiple invite records over time; only the most recent
 * non-expired one is surfaced to the owner via latestForGroup().
 */
class Invite
{
    /**
     * Generate a new invite token for the given group and persist it.
     *
     * @param int $groupId    The group this token grants access to.
     * @param int $createdBy  ID of the user (owner) who generated the link.
     * @return string  The raw 32-char hex token (embed in the join URL).
     */
    public static function create(int $groupId, int $createdBy): string
    {
        $db    = Database::getInstance();
        $token = bin2hex(random_bytes(16)); // cryptographically random, 32 hex chars
        $stmt  = $db->prepare(
            'INSERT INTO invites (group_id, token, created_at, expires_at, created_by)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), ?)'
        );
        $stmt->execute([$groupId, $token, $createdBy]);
        return $token;
    }

    /**
     * Find an invite record by its token string.
     *
     * Returns the full row regardless of expiry; the caller is responsible for
     * checking expires_at if time-validity matters (see InviteController).
     *
     * @param string $token  32-char hex token from the URL.
     * @return array|null  Invite row, or null if the token doesn't exist.
     */
    public static function findByToken(string $token): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM invites WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Return the most recently created, still-valid invite for a group.
     *
     * Used by GroupController::show() to display the current invite link in
     * the group owner's sidebar. Returns null when there is no active invite,
     * prompting the owner to generate a new one.
     *
     * @param int $groupId  Group to look up.
     * @return array|null  Most recent active invite row, or null.
     */
    public static function latestForGroup(int $groupId): ?array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT * FROM invites
             WHERE group_id = ? AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetch() ?: null;
    }
}
