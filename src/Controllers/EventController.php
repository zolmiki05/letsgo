<?php
/**
 * EventController – programme idea lifecycle management.
 *
 * Route summary:
 *   GET  /groups/{id}/events/create  → createForm()    Show the new-event form
 *   POST /groups/{id}/events/create  → create()        Submit a new event
 *   GET  /events/{id}                → show()          Event detail with feedback
 *   POST /events/{id}/delete         → delete()        Delete event (creator only)
 *   POST /events/{id}/status         → updateStatus()  Change status (creator only)
 *   POST /events/{id}/feedback       → saveFeedback()  Submit/update 3-scale rating
 *
 * Access rules:
 *   - createForm/create: user must be a group member.
 *   - show/saveFeedback: user must be a group member.
 *   - delete/updateStatus: user must be the event creator.
 *
 * Date handling:
 *   Each of the three date fields (event_date/date_text, deadline_signup/
 *   deadline_signup_text, deadline_decision/deadline_decision_text) has a
 *   'mode' POST parameter selecting between 'date' (structured DATE column)
 *   and 'text' (free-text VARCHAR). Only the selected mode's value is saved;
 *   the other is stored as NULL.
 */
class EventController
{
    // ── Create ────────────────────────────────────────────────────────────────

    /**
     * Display the new-event creation form.
     * The form is scoped to the group identified by {id} in the URL.
     * Redirects to / if the group doesn't exist or the user is not a member.
     */
    public function createForm(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];
        $group   = Group::findById($groupId);

        if (!$group || !Group::isMember($groupId, $userId)) {
            redirect('/');
        }

        render('event/create', [
            'pageTitle' => Lang::t('event.create_title'),
            'group'     => $group,
            'error'     => Session::flash('error'),
        ]);
    }

    /**
     * Process the new-event form submission.
     *
     * For each date field pair, reads the corresponding '_mode' POST variable
     * to decide which column to populate:
     *   mode = 'date'  → store structured DATE value, set text to NULL
     *   mode = 'text'  → store free-text value, set DATE to NULL
     *
     * Validates title is non-empty (the only required field).
     * Redirects to the new event's detail page on success.
     */
    public function create(array $params): void
    {
        $userId  = requireAuth();
        $groupId = (int)$params['id'];

        if (!Group::isMember($groupId, $userId)) {
            redirect('/');
        }

        $title = trim($_POST['title'] ?? '');
        if (!$title) {
            Session::flash('error', Lang::t('event.errors.title_required'));
            redirect('/groups/' . $groupId . '/events/create');
        }

        // ── Date mode resolution ──────────────────────────────────────────────
        // Each field pair has a radio button (_mode) that selects 'date' or 'text'.
        // Only the active mode's value is stored; the other is set to null.

        // Event date
        $dateMode  = $_POST['date_mode'] ?? 'text';
        $eventDate = ($dateMode === 'date') ? ($_POST['event_date'] ?? '') : '';
        $dateText  = ($dateMode === 'text') ? trim($_POST['date_text'] ?? '') : '';

        // Signup deadline
        $dlSignupMode = $_POST['deadline_signup_mode'] ?? 'date';
        $dlSignup     = ($dlSignupMode === 'date') ? ($_POST['deadline_signup']      ?? '') : '';
        $dlSignupTxt  = ($dlSignupMode === 'text') ? trim($_POST['deadline_signup_text']  ?? '') : '';

        // Decision deadline
        $dlDecMode  = $_POST['deadline_decision_mode'] ?? 'date';
        $dlDecision = ($dlDecMode === 'date') ? ($_POST['deadline_decision']      ?? '') : '';
        $dlDecTxt   = ($dlDecMode === 'text') ? trim($_POST['deadline_decision_text'] ?? '') : '';

        $eventId = Event::create([
            'group_id'                => $groupId,
            'creator_id'              => $userId,
            'title'                   => $title,
            'description'             => trim($_POST['description'] ?? ''),
            'location'                => trim($_POST['location'] ?? ''),
            'event_date'              => $eventDate,
            'date_text'               => $dateText,
            'deadline_signup'         => $dlSignup,
            'deadline_signup_text'    => $dlSignupTxt,
            'deadline_decision'       => $dlDecision,
            'deadline_decision_text'  => $dlDecTxt,
            'cost'                    => trim($_POST['cost'] ?? ''),
            'notes'                   => trim($_POST['notes'] ?? ''),
        ]);

        redirect('/events/' . $eventId);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    /**
     * Display an event's detail page.
     *
     * Includes:
     *   - Event metadata (description, location, date, deadlines, cost, notes)
     *   - Status management form (creator only)
     *   - Delete button (creator only)
     *   - Three-scale feedback form (pre-filled if the user has already responded)
     *   - Sidebar: group averages + all individual responses
     *
     * Returns 404 if the event doesn't exist or the user is not a group member.
     */
    public function show(array $params): void
    {
        $userId  = requireAuth();
        $eventId = (int)$params['id'];
        $event   = Event::findById($eventId);

        if (!$event || !Group::isMember((int)$event['group_id'], $userId)) {
            http_response_code(404);
            render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $creator = User::findById((int)$event['creator_id']);

        render('event/show', [
            'pageTitle' => e($event['title']),
            'event'     => $event,
            'group'     => Group::findById((int)$event['group_id']),
            'creator'   => $creator,
            'responses' => Response::forEvent($eventId),
            'averages'  => Response::averages($eventId), // null if no responses yet
            'myResp'    => Response::findByEventAndUser($eventId, $userId),
            'isCreator' => (int)$event['creator_id'] === $userId,
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    /**
     * Delete an event (creator only).
     * Cascade-deletes all associated responses.
     * Redirects to the group page on success.
     */
    public function delete(array $params): void
    {
        $userId  = requireAuth();
        $eventId = (int)$params['id'];
        $event   = Event::findById($eventId);

        if (!$event) redirect('/');

        if ((int)$event['creator_id'] !== $userId) {
            Session::flash('error', Lang::t('event.errors.not_creator'));
            redirect('/events/' . $eventId);
        }

        $groupId = (int)$event['group_id'];
        Event::delete($eventId);
        redirect('/groups/' . $groupId);
    }

    // ── Status ────────────────────────────────────────────────────────────────

    /**
     * Update the event's status (creator only).
     * Invalid status values are silently ignored by Event::updateStatus().
     * Redirects back to the event page.
     */
    public function updateStatus(array $params): void
    {
        $userId  = requireAuth();
        $eventId = (int)$params['id'];
        $event   = Event::findById($eventId);

        if (!$event) redirect('/');

        if ((int)$event['creator_id'] !== $userId) {
            Session::flash('error', Lang::t('event.errors.not_creator'));
            redirect('/events/' . $eventId);
        }

        Event::updateStatus($eventId, $_POST['status'] ?? '');
        redirect('/events/' . $eventId);
    }

    // ── Feedback ──────────────────────────────────────────────────────────────

    /**
     * Save or update a user's three-scale feedback for an event.
     *
     * Validates all three scales are integers in the range [1, 10].
     * Uses Response::upsert() which transparently handles first submission
     * and subsequent edits.
     * Redirects back to the event page with a success flash on success.
     */
    public function saveFeedback(array $params): void
    {
        $userId  = requireAuth();
        $eventId = (int)$params['id'];
        $event   = Event::findById($eventId);

        if (!$event || !Group::isMember((int)$event['group_id'], $userId)) {
            redirect('/');
        }

        $interest    = (int)($_POST['interest_level']    ?? 0);
        $mood        = (int)($_POST['mood_level']        ?? 0);
        $willingness = (int)($_POST['willingness_level'] ?? 0);

        // All three values must be in the valid 1–10 range
        if (
            $interest < 1    || $interest > 10    ||
            $mood < 1        || $mood > 10        ||
            $willingness < 1 || $willingness > 10
        ) {
            Session::flash('error', Lang::t('event.errors.invalid_feedback'));
            redirect('/events/' . $eventId);
        }

        Response::upsert($eventId, $userId, $interest, $mood, $willingness);
        Session::flash('success', Lang::t('event.feedback_saved'));
        redirect('/events/' . $eventId);
    }
}
