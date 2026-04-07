<?php
/**
 * DashboardController – the main landing page after login.
 *
 * Route summary:
 *   GET /  → index()  Dashboard (requires authentication)
 *
 * The dashboard shows two sections:
 *   1. Upcoming deadlines – events with a structured deadline_signup or
 *      deadline_decision falling within the next 7 days, across all of the
 *      user's groups.
 *   2. My groups – cards for every group the user belongs to, with member count.
 *
 * Both data sets are fetched in a single controller method to minimise DB
 * round-trips; each query is fully covered by the appropriate model method.
 */
class DashboardController
{
    /**
     * Render the dashboard view.
     *
     * requireAuth() ensures the user is logged in; unauthenticated visitors
     * are redirected to /login (with the intended URL saved in session so that
     * invite links survive the redirect).
     */
    public function index(array $params): void
    {
        $userId = requireAuth();

        render('dashboard/index', [
            'pageTitle' => Lang::t('dashboard.title'),
            'groups'    => Group::forUser($userId),           // groups with member_count
            'deadlines' => Event::upcomingDeadlines($userId), // next 7 days, all groups
        ]);
    }
}
