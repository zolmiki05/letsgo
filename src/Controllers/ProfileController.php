<?php
/**
 * ProfileController – notification preferences and locale settings.
 *
 * Route summary:
 *   GET  /profile/notifications  → notificationsForm()   Show the preferences form
 *   POST /profile/notifications  → saveNotifications()   Persist opt-out choices
 *   POST /profile/locale         → setLocale()           Switch UI language
 *
 * Access: all routes require an authenticated session.
 */
class ProfileController
{
    /**
     * Display the notification preferences form.
     *
     * Pre-fills checkboxes based on the current user's disabled types.
     */
    public function notificationsForm(array $params): void
    {
        $userId = requireAuth();

        render('profile/notifications', [
            'pageTitle' => Lang::t('profile.notifications_title'),
            'types'     => NotificationPreference::TYPES,
            'disabled'  => NotificationPreference::disabledTypesForUser($userId),
            'success'   => Session::flash('success'),
        ]);
    }

    /**
     * Save notification preferences.
     *
     * Each checkbox in the form represents a notification type.
     * Checked = enabled (remove opt-out row); unchecked = disabled (add opt-out row).
     * POST field: enabled_types[] – list of type strings that are ENABLED.
     */
    public function saveNotifications(array $params): void
    {
        $userId       = requireAuth();
        $enabledTypes = (array)($_POST['enabled_types'] ?? []);

        foreach (NotificationPreference::TYPES as $type) {
            $isEnabled = in_array($type, $enabledTypes, true);
            NotificationPreference::setDisabled($userId, $type, !$isEnabled);
        }

        Session::flash('success', Lang::t('profile.notifications_saved'));
        redirect('/profile/notifications');
    }

    /**
     * Switch the user's preferred language.
     *
     * Accepted locales: 'hu', 'en' (others are silently rejected to prevent path traversal).
     * Persists the choice to users.locale and reloads the page.
     */
    public function setLocale(array $params): void
    {
        $userId = requireAuth();
        $locale = $_POST['locale'] ?? 'hu';

        $allowed = ['hu', 'en'];
        if (!in_array($locale, $allowed, true)) {
            $locale = 'hu';
        }

        User::setLocale($userId, $locale);
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
