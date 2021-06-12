<?php

use Appwrite\Auth\Auth;
use Appwrite\Database\Validator\UID;
use Appwrite\Detector\Detector;
use Appwrite\Template\Template;
use Appwrite\Utopia\Response;
use Appwrite\Network\Validator\Email;
use Appwrite\Network\Validator\Host;
use Utopia\App;
use Utopia\Exception;
use Utopia\Config\Config;
use Utopia\Validator\Text;
use Utopia\Validator\Range;
use Utopia\Validator\ArrayList;
use Utopia\Validator\WhiteList;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\Key;

App::post('/v1/teams')
    ->desc('Create Team')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.create')
    ->label('scope', 'teams.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'create')
    ->label('sdk.description', '/docs/references/teams/create-team.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TEAM)
    ->param('name', null, new Text(128), 'Team name. Max length: 128 chars.')
    ->param('roles', ['owner'], new ArrayList(new Key()), 'Array of strings. Use this param to set the roles in the team for the user who created it. The default role is **owner**. A role can be any string. Learn more about [roles and permissions](/docs/permissions). Max length for each role is 32 chars.', true)
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->action(function ($name, $roles, $response, $user, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */

        Authorization::disable();

        $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::$roles);
        $isAppUser = Auth::isAppUser(Authorization::$roles);

        $teamId = $dbForInternal->getId();
        $team = $dbForInternal->createDocument('teams', new Document([
            '$id' => $teamId ,
            '$read' => ['team:'.$teamId],
            '$write' => ['team:'.$teamId .'/owner'],
            'name' => $name,
            'sum' => ($isPrivilegedUser || $isAppUser) ? 0 : 1,
            'dateCreated' => \time(),
        ]));

        Authorization::reset();

        if (!$isPrivilegedUser && !$isAppUser) { // Don't add user on server mode
            $membership = new Document([
                '$read' => ['user:'.$user->getId(), 'team:'.$team->getId()],
                '$write' => ['user:'.$user->getId(), 'team:'.$team->getId().'/owner'],
                'userId' => $user->getId(),
                'teamId' => $team->getId(),
                'roles' => $roles,
                'invited' => \time(),
                'joined' => \time(),
                'confirm' => true,
                'secret' => '',
            ]);

            $membership = $dbForInternal->createDocument('memberships', $membership);

            // Attach user to team
            $user->setAttribute('memberships', $membership, Document::SET_TYPE_APPEND);
            $user = $dbForInternal->updateDocument('users', $user->getId(), $user);
        }

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic2($team, Response::MODEL_TEAM);
    });

App::get('/v1/teams')
    ->desc('List Teams')
    ->groups(['api', 'teams'])
    ->label('scope', 'teams.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'list')
    ->label('sdk.description', '/docs/references/teams/list-teams.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TEAM_LIST)
    ->param('search', '', new Text(256), 'Search term to filter your list results. Max length: 256 chars.', true)
    ->param('limit', 25, new Range(0, 100), 'Results limit value. By default will return maximum 25 results. Maximum of 100 results allowed per request.', true)
    ->param('offset', 0, new Range(0, 2000), 'Results offset. The default value is 0. Use this param to manage pagination.', true)
    ->param('orderType', 'ASC', new WhiteList(['ASC', 'DESC'], true), 'Order result by ASC or DESC order.', true)
    ->inject('response')
    ->inject('dbForInternal')
    ->action(function ($search, $limit, $offset, $orderType, $response, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */

        $queries = ($search) ? [new Query('name', Query::TYPE_SEARCH, [$search])] : [];

        $results = $dbForInternal->find('teams', $queries, $limit, $offset, ['_id'], [$orderType]);
        $sum = $dbForInternal->count('teams', $queries, APP_LIMIT_COUNT);

        $response->dynamic2(new Document([
            'teams' => $results,
            'sum' => $sum,
        ]), Response::MODEL_TEAM_LIST);
    });

App::get('/v1/teams/:teamId')
    ->desc('Get Team')
    ->groups(['api', 'teams'])
    ->label('scope', 'teams.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'get')
    ->label('sdk.description', '/docs/references/teams/get-team.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TEAM)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->inject('response')
    ->inject('dbForInternal')
    ->action(function ($teamId, $response, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */

        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        $response->dynamic2($team, Response::MODEL_TEAM);
    });

App::put('/v1/teams/:teamId')
    ->desc('Update Team')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.update')
    ->label('scope', 'teams.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'update')
    ->label('sdk.description', '/docs/references/teams/update-team.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TEAM)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('name', null, new Text(128), 'Team name. Max length: 128 chars.')
    ->inject('response')
    ->inject('dbForInternal')
    ->action(function ($teamId, $name, $response, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */

        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        $team = $dbForInternal->updateDocument('teams', $team->getId(), $team->setAttribute('name', $name));

        $response->dynamic2($team, Response::MODEL_TEAM);
    });

App::delete('/v1/teams/:teamId')
    ->desc('Delete Team')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.delete')
    ->label('scope', 'teams.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'delete')
    ->label('sdk.description', '/docs/references/teams/delete-team.md')
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->inject('response')
    ->inject('dbForInternal')
    ->inject('events')
    ->action(function ($teamId, $response, $dbForInternal, $events) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $events */

        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        $memberships = $dbForInternal->find('memberships', [
            new Query('teamId', Query::TYPE_EQUAL, [$teamId]),
        ], 2000, 0); // TODO fix members limit

        // TODO delete all members individually from the user object
        foreach ($memberships as $membership) {
            if (!$dbForInternal->deleteDocument('memberships', $membership->getId())) {
                throw new Exception('Failed to remove membership for team from DB', 500);
            }
        }

        if (!$dbForInternal->deleteDocument('teams', $teamId)) {
            throw new Exception('Failed to remove team from DB', 500);
        }

        $events
            ->setParam('eventData', $response->output2($team, Response::MODEL_TEAM))
        ;

        $response->noContent();
    });

App::post('/v1/teams/:teamId/memberships')
    ->desc('Create Team Membership')
    ->groups(['api', 'teams', 'auth'])
    ->label('event', 'teams.memberships.create')
    ->label('scope', 'teams.write')
    ->label('auth.type', 'invites')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'createMembership')
    ->label('sdk.description', '/docs/references/teams/create-team-membership.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_MEMBERSHIP)
    ->label('abuse-limit', 10)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('email', '', new Email(), 'New team member email.')
    ->param('name', '', new Text(128), 'New team member name. Max length: 128 chars.', true)
    ->param('roles', [], new ArrayList(new Key()), 'Array of strings. Use this param to set the user roles in the team. A role can be any string. Learn more about [roles and permissions](/docs/permissions). Max length for each role is 32 chars.')
    ->param('url', '', function ($clients) { return new Host($clients); }, 'URL to redirect the user back to your app from the invitation email.  Only URLs from hostnames in your project platform list are allowed. This requirement helps to prevent an [open redirect](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html) attack against your project API.', false, ['clients']) // TODO add our own built-in confirm page
    ->inject('response')
    ->inject('project')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('locale')
    ->inject('audits')
    ->inject('mails')
    ->action(function ($teamId, $email, $name, $roles, $url, $response, $project, $user, $dbForInternal, $locale, $audits, $mails) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Appwrite\Database\Document $project */
        /** @var Appwrite\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $mails */

        $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::$roles);
        $isAppUser = Auth::isAppUser(Authorization::$roles);
        
        $email = \strtolower($email);
        $name = (empty($name)) ? $email : $name;
        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        $invitee = $dbForInternal->findFirst('users', [new Query('email', Query::TYPE_EQUAL, [$email])], 1); // Get user by email address

        if (empty($invitee)) { // Create new user if no user with same email found

            $limit = $project->getAttribute('usersAuthLimit', 0);
        
            if ($limit !== 0 && $project->getId() !== 'console') { // check users limit, console invites are allways allowed.
                $sum = $dbForInternal->count('users', [], APP_LIMIT_USERS);
    
                if($sum >= $limit) {
                    throw new Exception('Project registration is restricted. Contact your administrator for more information.', 501);
                }
            }

            Authorization::disable();

            try {
                $userId = $dbForInternal->getId();
                $invitee = $dbForInternal->createDocument('users', new Document([
                    '$id' => $userId,
                    '$read' => ['user:'.$userId, 'role:all'],
                    '$write' => ['user:'.$userId],
                    'email' => $email,
                    'emailVerification' => false,
                    'status' => Auth::USER_STATUS_UNACTIVATED,
                    'password' => Auth::passwordHash(Auth::passwordGenerator()),
                    /** 
                     * Set the password update time to 0 for users created using 
                     * team invite and OAuth to allow password updates without an 
                     * old password 
                     */
                    'passwordUpdate' => 0,
                    'registration' => \time(),
                    'reset' => false,
                    'name' => $name,
                    'prefs' => [],
                    'sessions' => [],
                    'tokens' => [],
                    'memberships' => [],
                ]));
            } catch (Duplicate $th) {
                throw new Exception('Account already exists', 409);
            }

            Authorization::reset();
        }

        $isOwner = Authorization::isRole('team:'.$team->getId().'/owner');;

        if (!$isOwner && !$isPrivilegedUser && !$isAppUser) { // Not owner, not admin, not app (server)
            throw new Exception('User is not allowed to send invitations for this team', 401);
        }

        $secret = Auth::tokenGenerator();

        $membership = new Document([
            '$id' => $dbForInternal->getId(),
            '$read' => ['role:all'],
            '$write' => ['user:'.$invitee->getId(), 'team:'.$team->getId().'/owner'],
            'userId' => $invitee->getId(),
            'teamId' => $team->getId(),
            'roles' => $roles,
            'invited' => \time(),
            'joined' => ($isPrivilegedUser || $isAppUser) ? \time() : 0,
            'confirm' => ($isPrivilegedUser || $isAppUser),
            'secret' => Auth::hash($secret),
        ]);

        if ($isPrivilegedUser || $isAppUser) { // Allow admin to create membership
            Authorization::disable();
            try {
                $membership = $dbForInternal->createDocument('memberships', $membership);
            } catch (Duplicate $th) {
                throw new Exception('User has already been invited or is already a member of this team', 409);
            }

            $team = $dbForInternal->updateDocument('teams', $team->getId(), $team->setAttribute('sum', $team->getAttribute('sum', 0) + 1));

            // Attach user to team
            $invitee->setAttribute('memberships', $membership, Document::SET_TYPE_APPEND);

            $invitee = $dbForInternal->updateDocument('users', $invitee->getId(), $invitee);

            Authorization::reset();
        } else {
            try {
                $membership = $dbForInternal->createDocument('memberships', $membership);
            } catch (Duplicate $th) {
                throw new Exception('User has already been invited or is already a member of this team', 409);
            }
        }

        $url = Template::parseURL($url);
        $url['query'] = Template::mergeQuery(((isset($url['query'])) ? $url['query'] : ''), ['membershipId' => $membership->getId(), 'teamId' => $team->getId(), 'userId' => $invitee->getId(), 'secret' => $secret, 'teamId' => $teamId]);
        $url = Template::unParseURL($url);

        $body = new Template(__DIR__.'/../../config/locale/templates/email-base.tpl');
        $content = new Template(__DIR__.'/../../config/locale/translations/templates/'.$locale->getText('account.emails.invitation.body'));
        $cta = new Template(__DIR__.'/../../config/locale/templates/email-cta.tpl');
        $title = \sprintf($locale->getText('account.emails.invitation.title'), $team->getAttribute('name', '[TEAM-NAME]'), $project->getAttribute('name', ['[APP-NAME]']));
        
        $body
            ->setParam('{{content}}', $content->render(false))
            ->setParam('{{cta}}', $cta->render())
            ->setParam('{{title}}', $title)
            ->setParam('{{direction}}', $locale->getText('settings.direction'))
            ->setParam('{{project}}', $project->getAttribute('name', ['[APP-NAME]']))
            ->setParam('{{team}}', $team->getAttribute('name', '[TEAM-NAME]'))
            ->setParam('{{owner}}', $user->getAttribute('name', ''))
            ->setParam('{{redirect}}', $url)
            ->setParam('{{bg-body}}', '#f7f7f7')
            ->setParam('{{bg-content}}', '#ffffff')
            ->setParam('{{bg-cta}}', '#073b4c')
            ->setParam('{{text-content}}', '#000000')
            ->setParam('{{text-cta}}', '#ffffff')
        ;

        if (!$isPrivilegedUser && !$isAppUser) { // No need of confirmation when in admin or app mode
            $mails
                ->setParam('event', 'teams.memberships.create')
                ->setParam('from', ($project->getId() === 'console') ? '' : \sprintf($locale->getText('account.emails.team'), $project->getAttribute('name')))
                ->setParam('recipient', $email)
                ->setParam('name', $name)
                ->setParam('subject', $title)
                ->setParam('body', $body->render())
                ->trigger()
            ;
        }

        $audits
            ->setParam('userId', $invitee->getId())
            ->setParam('event', 'teams.memberships.create')
            ->setParam('resource', 'teams/'.$teamId)
        ;

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic2($membership
            ->setAttribute('email', $email)
            ->setAttribute('name', $name)
        , Response::MODEL_MEMBERSHIP);
    });

App::get('/v1/teams/:teamId/memberships')
    ->desc('Get Team Memberships')
    ->groups(['api', 'teams'])
    ->label('scope', 'teams.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'getMemberships')
    ->label('sdk.description', '/docs/references/teams/get-team-members.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_MEMBERSHIP_LIST)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('search', '', new Text(256), 'Search term to filter your list results. Max length: 256 chars.', true)
    ->param('limit', 25, new Range(0, 100), 'Results limit value. By default will return maximum 25 results. Maximum of 100 results allowed per request.', true)
    ->param('offset', 0, new Range(0, 2000), 'Results offset. The default value is 0. Use this param to manage pagination.', true)
    ->param('orderType', 'ASC', new WhiteList(['ASC', 'DESC'], true), 'Order result by ASC or DESC order.', true)
    ->inject('response')
    ->inject('dbForInternal')
    ->action(function ($teamId, $search, $limit, $offset, $orderType, $response, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */

        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        $memberships = $dbForInternal->find('memberships', [new Query('teamId', Query::TYPE_EQUAL, [$teamId])], $limit, $offset, ['_id'], [$orderType]);
        $sum = $dbForInternal->count('memberships', [new Query('teamId', Query::TYPE_EQUAL, [$teamId])], APP_LIMIT_COUNT);
        $users = [];

        foreach ($memberships as $membership) {
            if (empty($membership->getAttribute('userId', null))) {
                continue;
            }

            $temp = $dbForInternal->getDocument('users', $membership->getAttribute('userId', null))->getArrayCopy(['email', 'name']);

            $users[] = new Document(\array_merge($temp, $membership->getArrayCopy()));
        }

        $response->dynamic2(new Document([
            'memberships' => $users,
            'sum' => $sum,
        ]), Response::MODEL_MEMBERSHIP_LIST);
    });

App::patch('/v1/teams/:teamId/memberships/:membershipId')
    ->desc('Update Membership Roles')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.memberships.update')
    ->label('scope', 'teams.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'updateMembershipRoles')
    ->label('sdk.description', '/docs/references/teams/update-team-membership-roles.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_MEMBERSHIP)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('membershipId', '', new UID(), 'Membership ID.')
    ->param('roles', [], new ArrayList(new Key()), 'Array of strings. Use this param to set the user roles in the team. A role can be any string. Learn more about [roles and permissions](/docs/permissions). Max length for each role is 32 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($teamId, $membershipId, $roles, $request, $response, $user, $dbForInternal, $audits) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Appwrite\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $team = $dbForInternal->getDocument('teams', $teamId);
        if ($team->isEmpty()) {
            throw new Exception('Team not found', 404);
        }

        $membership = $dbForInternal->getDocument('memberships', $membershipId);
        if ($membership->isEmpty()) {
            throw new Exception('Membership not found', 404);
        }

        $profile = $dbForInternal->getDocument('users', $membership->getAttribute('userId'));
        if ($profile->isEmpty()) {
            throw new Exception('User not found', 404);
        }

        $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::$roles);
        $isAppUser = Auth::isAppUser(Authorization::$roles);
        $isOwner = Authorization::isRole('team:'.$team->getId().'/owner');;
        
        if (!$isOwner && !$isPrivilegedUser && !$isAppUser) { // Not owner, not admin, not app (server)
            throw new Exception('User is not allowed to modify roles', 401);
        }

        // Update the roles
        $membership->setAttribute('roles', $roles);
        $membership = $dbForInternal->updateDocument('memberships', $membership->getId(), $membership);

        //TODO sync updated membership in the user $profile object using TYPE_REPLACE

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'teams.memberships.update')
            ->setParam('resource', 'teams/'.$teamId)
        ;

        $response->dynamic2($membership, Response::MODEL_MEMBERSHIP);
    });

App::patch('/v1/teams/:teamId/memberships/:membershipId/status')
    ->desc('Update Team Membership Status')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.memberships.update.status')
    ->label('scope', 'public')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'updateMembershipStatus')
    ->label('sdk.description', '/docs/references/teams/update-team-membership-status.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_MEMBERSHIP)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('membershipId', '', new UID(), 'Membership ID.')
    ->param('userId', '', new UID(), 'User unique ID.')
    ->param('secret', '', new Text(256), 'Secret key.')
    ->inject('request')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('geodb')
    ->inject('audits')
    ->action(function ($teamId, $membershipId, $userId, $secret, $request, $response, $user, $dbForInternal, $geodb, $audits) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Appwrite\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var MaxMind\Db\Reader $geodb */
        /** @var Appwrite\Event\Event $audits */

        $protocol = $request->getProtocol();

        $membership = $dbForInternal->getDocument('memberships', $membershipId);

        if (empty($membership->getId())) {
            throw new Exception('Membership not found', 404);
        }

        if ($membership->getAttribute('teamId') !== $teamId) {
            throw new Exception('Team IDs don\'t match', 404);
        }

        Authorization::disable();

        $team = $dbForInternal->getDocument('teams', $teamId);
        
        Authorization::reset();

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        if (Auth::hash($secret) !== $membership->getAttribute('secret')) {
            throw new Exception('Secret key not valid', 401);
        }

        if ($userId != $membership->getAttribute('userId')) {
            throw new Exception('Invite does not belong to current user ('.$user->getAttribute('email').')', 401);
        }

        if (empty($user->getId())) {
            $user = $dbForInternal->getDocument('users', $userId); // Get user
        }

        if ($membership->getAttribute('userId') !== $user->getId()) {
            throw new Exception('Invite does not belong to current user ('.$user->getAttribute('email').')', 401);
        }

        $membership // Attach user to team
            ->setAttribute('joined', \time())
            ->setAttribute('confirm', true)
        ;

        $user
            ->setAttribute('emailVerification', true)
            ->setAttribute('memberships', $membership, Document::SET_TYPE_APPEND)
        ;

        // Log user in

        $detector = new Detector($request->getUserAgent('UNKNOWN'));
        $record = $geodb->get($request->getIP());
        $expiry = \time() + Auth::TOKEN_EXPIRATION_LOGIN_LONG;
        $secret = Auth::tokenGenerator();
        $session = new Document(array_merge([
            '$id' => $dbForInternal->getId(),
            'userId' => $user->getId(),
            'provider' => Auth::SESSION_PROVIDER_EMAIL,
            'providerUid' => $user->getAttribute('email'),
            'secret' => Auth::hash($secret), // One way hash encryption to protect DB leak
            'expire' => $expiry,
            'userAgent' => $request->getUserAgent('UNKNOWN'),
            'ip' => $request->getIP(),
            'countryCode' => ($record) ? \strtolower($record['country']['iso_code']) : '--',
        ], $detector->getOS(), $detector->getClient(), $detector->getDevice()));

        $user->setAttribute('sessions', $session, Document::SET_TYPE_APPEND);

        Authorization::setRole('user:'.$userId);

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user);
        $membership = $dbForInternal->updateDocument('memberships', $membership->getId(), $membership);

        Authorization::disable();

        $team = $dbForInternal->updateDocument('teams', $team->getId(), $team->setAttribute('sum', $team->getAttribute('sum', 0) + 1));

        Authorization::reset();

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'teams.memberships.update.status')
            ->setParam('resource', 'teams/'.$teamId)
        ;

        if (!Config::getParam('domainVerification')) {
            $response
                ->addHeader('X-Fallback-Cookies', \json_encode([Auth::$cookieName => Auth::encodeSession($user->getId(), $secret)]))
            ;
        }

        $response
            ->addCookie(Auth::$cookieName.'_legacy', Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
            ->addCookie(Auth::$cookieName, Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
        ;

        $response->dynamic2($membership
            ->setAttribute('email', $user->getAttribute('email'))
            ->setAttribute('name', $user->getAttribute('name'))
        , Response::MODEL_MEMBERSHIP);
    });

App::delete('/v1/teams/:teamId/memberships/:membershipId')
    ->desc('Delete Team Membership')
    ->groups(['api', 'teams'])
    ->label('event', 'teams.memberships.delete')
    ->label('scope', 'teams.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'teams')
    ->label('sdk.method', 'deleteMembership')
    ->label('sdk.description', '/docs/references/teams/delete-team-membership.md')
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('teamId', '', new UID(), 'Team unique ID.')
    ->param('membershipId', '', new UID(), 'Membership ID.')
    ->inject('response')
    ->inject('dbForInternal')
    ->inject('audits')
    ->inject('events')
    ->action(function ($teamId, $membershipId, $response, $dbForInternal, $audits, $events) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */

        $membership = $dbForInternal->getDocument('memberships', $membershipId);

        if (empty($membership->getId())) {
            throw new Exception('Invite not found', 404);
        }

        if ($membership->getAttribute('teamId') !== $teamId) {
            throw new Exception('Team IDs don\'t match', 404);
        }

        $user = $dbForInternal->getDocument('users', $membership->getAttribute('userId'));

        if ($user->isEmpty()) {
            throw new Exception('User not found', 404);
        }

        $team = $dbForInternal->getDocument('teams', $teamId);

        if (empty($team->getId())) {
            throw new Exception('Team not found', 404);
        }

        if (!$dbForInternal->deleteDocument('memberships', $membership->getId())) {
            throw new Exception('Failed to remove membership from DB', 500);
        }

        $memberships = $user->getAttribute('memberships', []);

        foreach ($memberships as $key => $child) { 
            /** @var Document $child */

            if ($membershipId == $child->getId()) {
                unset($memberships[$key]);
                break;
            }
        }

        Authorization::disable();

        $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('memberships', $memberships));

        Authorization::reset();

        if ($membership->getAttribute('confirm')) { // Count only confirmed members
            $team = $dbForInternal->updateDocument('teams', $team->getId(), $team->setAttribute('sum', \max($team->getAttribute('sum', 0) - 1, 0)));
        }

        $audits
            ->setParam('userId', $membership->getAttribute('userId'))
            ->setParam('event', 'teams.memberships.delete')
            ->setParam('resource', 'teams/'.$teamId)
        ;

        $events
            ->setParam('eventData', $response->output2($membership, Response::MODEL_MEMBERSHIP))
        ;

        $response->noContent();
    });
