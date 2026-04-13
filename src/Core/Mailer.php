<?php
/**
 * Mailer – HTML email dispatch via SMTP.
 *
 * Env vars:
 *   MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION (ssl|tls|''), MAIL_USER, MAIL_PASS,
 *   MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 *
 * If MAIL_HOST is empty all calls are silently skipped.
 */
class Mailer
{
    // ── Public helpers ────────────────────────────────────────────────────────

    public static function sendInvite(string $to, string $token): void
    {
        self::send($to, Lang::t('email.invite_subject'), self::renderEmail('invite', [
            'token'       => $token,
            'registerUrl' => appBaseUrl() . '/register?invite=' . urlencode($token),
        ]));
    }

    public static function sendPasswordReset(array $user, string $token): void
    {
        $resetUrl = appBaseUrl() . '/reset-password?token=' . urlencode($token);
        self::send($user['email'], Lang::t('email.password_reset_subject'),
            self::renderEmail('password_reset', ['user' => $user, 'resetUrl' => $resetUrl]));
    }

    public static function sendPasswordChanged(array $user): void
    {
        self::send($user['email'], Lang::t('email.password_changed_subject'),
            self::renderEmail('password_changed', ['user' => $user]));
    }

    public static function sendEventCreated(
        array $event, array $group, array $members, array $creator, array $slots, int $actorId
    ): void {
        $subject  = Lang::t('email.event_created_subject', ['title' => $event['title']]);
        $eventUrl = appBaseUrl() . '/events/' . $event['id'];
        foreach ($members as $m) {
            if ((int)$m['id'] === $actorId) continue;
            self::send($m['email'], $subject, self::renderEmail('event_created', [
                'event' => $event, 'group' => $group, 'creator' => $creator,
                'slots' => $slots,  'eventUrl' => $eventUrl,
            ]));
        }
    }

    public static function sendEventUpdated(
        array $event, array $group, array $members, array $creator, array $slots, int $actorId, array $actor
    ): void {
        $subject  = Lang::t('email.event_updated_subject', ['title' => $event['title']]);
        $eventUrl = appBaseUrl() . '/events/' . $event['id'];
        foreach ($members as $m) {
            if ((int)$m['id'] === $actorId) continue;
            self::send($m['email'], $subject, self::renderEmail('event_updated', [
                'event' => $event, 'group' => $group, 'creator' => $creator,
                'slots' => $slots,  'eventUrl' => $eventUrl, 'actor' => $actor,
            ]));
        }
    }

    /** Notify the event creator when a member submits feedback. */
    public static function sendFeedbackReceived(
        array $event, array $creator, array $actor, array $response
    ): void {
        if ((int)$creator['id'] === (int)$actor['id']) return; // no self-notify
        $subject  = Lang::t('email.feedback_received_subject', ['title' => $event['title']]);
        $eventUrl = appBaseUrl() . '/events/' . $event['id'];
        self::send($creator['email'], $subject, self::renderEmail('feedback_received', [
            'event'    => $event,
            'actor'    => $actor,
            'response' => $response,
            'eventUrl' => $eventUrl,
        ]));
    }

    /** Notify all group members (except actor) when event status changes. */
    public static function sendStatusChanged(
        array $event, array $group, array $members, array $actor, string $oldStatus, int $actorId
    ): void {
        $subject  = Lang::t('email.status_changed_subject', ['title' => $event['title']]);
        $eventUrl = appBaseUrl() . '/events/' . $event['id'];
        $slots    = EventTimeSlot::forEvent((int)$event['id']);
        foreach ($members as $m) {
            if ((int)$m['id'] === $actorId) continue;
            self::send($m['email'], $subject, self::renderEmail('status_changed', [
                'event'     => $event,
                'group'     => $group,
                'actor'     => $actor,
                'slots'     => $slots,
                'oldStatus' => $oldStatus,
                'eventUrl'  => $eventUrl,
            ]));
        }
    }

    /** Notify existing group members when a new member joins. */
    public static function sendMemberJoined(
        array $group, array $existingMembers, array $newMember
    ): void {
        $subject  = Lang::t('email.member_joined_subject', [
            'name' => $newMember['username'] ?? $newMember['email'],
        ]);
        $groupUrl = appBaseUrl() . '/groups/' . $group['id'];
        foreach ($existingMembers as $m) {
            if ((int)$m['id'] === (int)$newMember['id']) continue;
            self::send($m['email'], $subject, self::renderEmail('member_joined', [
                'group'     => $group,
                'newMember' => $newMember,
                'groupUrl'  => $groupUrl,
            ]));
        }
    }

    public static function sendEventDeleted(
        array $event, array $group, array $members, int $actorId
    ): void {
        $subject  = Lang::t('email.event_deleted_subject', ['title' => $event['title']]);
        $groupUrl = appBaseUrl() . '/groups/' . $group['id'];
        foreach ($members as $m) {
            if ((int)$m['id'] === $actorId) continue;
            self::send($m['email'], $subject, self::renderEmail('event_deleted', [
                'event' => $event, 'group' => $group, 'groupUrl' => $groupUrl,
            ]));
        }
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    public static function send(string $to, string $subject, string $html): bool
    {
        if (!(getenv('MAIL_HOST') ?: '')) return false;
        try {
            return self::smtp($to, $subject, $html);
        } catch (Throwable $e) {
            error_log('[Mailer] ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    private static function renderEmail(string $name, array $vars): string
    {
        $content = self::tpl($name, $vars);
        return self::tpl('layout', ['content' => $content] + $vars);
    }

    private static function tpl(string $name, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        include ROOT . '/src/Views/email/' . $name . '.php';
        return ob_get_clean();
    }

    // ── SMTP client ───────────────────────────────────────────────────────────

    private static function smtp(string $to, string $subject, string $html): bool
    {
        $host = getenv('MAIL_HOST')         ?: '';
        $port = (int)(getenv('MAIL_PORT')   ?: 587);
        $user = getenv('MAIL_USER')         ?: '';
        $pass = getenv('MAIL_PASS')         ?: '';
        $from = getenv('MAIL_FROM_ADDRESS') ?: $user;
        $name = getenv('MAIL_FROM_NAME')    ?: 'Letsgo';
        $enc  = strtolower(getenv('MAIL_ENCRYPTION') ?: 'tls');

        $ctx  = stream_context_create(['ssl' => [
            'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true,
        ]]);
        $conn = $enc === 'ssl'
            ? @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx)
            : @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);

        if (!$conn) throw new RuntimeException("Connect failed: {$errstr} ({$errno})");
        stream_set_timeout($conn, 15);

        $read = function () use ($conn): string {
            $r = '';
            while ($l = fgets($conn, 512)) {
                $r .= $l;
                if (isset($l[3]) && $l[3] === ' ') break;
            }
            return $r;
        };
        $w = function (string $c) use ($conn): void {
            $result = @fwrite($conn, $c . "\r\n");
            if ($result === false) {
                throw new RuntimeException("SMTP write failed (EPIPE) while sending: " . strtok($c, ' '));
            }
        };
        $code = fn(string $r): int => (int)substr($r, 0, 3);
        // Strip port from HTTP_HOST (e.g. "localhost:8080" → "localhost")
        $ehlo = strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':');

        $read();
        $w("EHLO {$ehlo}"); $read();

        if ($enc === 'tls') {
            $w('STARTTLS');
            $r = $read();
            if ($code($r) !== 220) throw new RuntimeException("STARTTLS: {$r}");
            if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
                throw new RuntimeException('TLS handshake failed');
            $w("EHLO {$ehlo}"); $read();
        }

        if ($user !== '') {
            $w('AUTH LOGIN'); $read();
            $w(base64_encode($user)); $read();
            $w(base64_encode($pass));
            $r = $read();
            if ($code($r) !== 235) throw new RuntimeException("AUTH failed: {$r}");
        }

        $w("MAIL FROM:<{$from}>"); $read();
        $w("RCPT TO:<{$to}>");
        $r = $read();
        if ($code($r) > 299) throw new RuntimeException("RCPT rejected: {$r}");

        $w('DATA'); $read();

        $bd  = 'b_' . bin2hex(random_bytes(6));
        $sub = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $frm = '=?UTF-8?B?' . base64_encode($name) . '?= <' . $from . '>';
        $txt = wordwrap(strip_tags($html), 76, "\r\n");
        $msg = "From: {$frm}\r\nTo: {$to}\r\nSubject: {$sub}\r\n"
             . "Message-ID: <" . uniqid('', true) . "@{$ehlo}>\r\n"
             . "Date: " . date('r') . "\r\nMIME-Version: 1.0\r\n"
             . "Content-Type: multipart/alternative; boundary=\"{$bd}\"\r\n\r\n"
             . "--{$bd}\r\nContent-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($txt))
             . "--{$bd}\r\nContent-Type: text/html; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
             . "--{$bd}--\r\n";
        $msg = preg_replace('/^\.$/m', '..', $msg);
        fwrite($conn, $msg . "\r\n.\r\n");
        $r = $read();
        $w('QUIT'); $read();
        fclose($conn);
        return $code($r) === 250;
    }
}
