<?php
/**
 * EventController – programme idea creation, detail view, status updates, and feedback.
 */
class EventController
{
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

        // Date: either a proper date or free text
        $dateMode     = $_POST['date_mode'] ?? 'text';
        $eventDate    = ($dateMode === 'date') ? ($_POST['event_date'] ?? '') : '';
        $dateText     = ($dateMode === 'text') ? trim($_POST['date_text'] ?? '') : '';

        // Deadline signup: date or text
        $dlSignupMode = $_POST['deadline_signup_mode'] ?? 'date';
        $dlSignup     = ($dlSignupMode === 'date') ? ($_POST['deadline_signup']      ?? '') : '';
        $dlSignupTxt  = ($dlSignupMode === 'text') ? trim($_POST['deadline_signup_text']  ?? '') : '';

        // Deadline decision: date or text
        $dlDecMode    = $_POST['deadline_decision_mode'] ?? 'date';
        $dlDecision   = ($dlDecMode === 'date') ? ($_POST['deadline_decision']      ?? '') : '';
        $dlDecTxt     = ($dlDecMode === 'text') ? trim($_POST['deadline_decision_text'] ?? '') : '';

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
            'averages'  => Response::averages($eventId),
            'myResp'    => Response::findByEventAndUser($eventId, $userId),
            'isCreator' => (int)$event['creator_id'] === $userId,
            'error'     => Session::flash('error'),
            'success'   => Session::flash('success'),
        ]);
    }

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
