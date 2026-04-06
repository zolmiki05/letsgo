<?php
/**
 * DashboardController – main page shown after login.
 * Lists the user's groups and highlights upcoming deadlines.
 */
class DashboardController
{
    public function index(array $params): void
    {
        $userId = requireAuth();

        render('dashboard/index', [
            'pageTitle' => Lang::t('dashboard.title'),
            'groups'    => Group::forUser($userId),
            'deadlines' => Event::upcomingDeadlines($userId),
        ]);
    }
}
