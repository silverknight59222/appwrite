<?php

use Ahc\Jwt\JWT;
use Appwrite\Auth\Auth;
use Appwrite\Auth\Validator\Password;
use Appwrite\Detector\Detector;
use Appwrite\Template\Template;
use Appwrite\OpenSSL\OpenSSL;
use Appwrite\URL\URL as URLParser;
use Appwrite\Utopia\Response;
use Appwrite\Network\Validator\Email;
use Appwrite\Network\Validator\Host;
use Appwrite\Network\Validator\URL;
use Utopia\App;
use Utopia\Exception;
use Utopia\Config\Config;
use Utopia\Validator\Assoc;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;
use Utopia\Validator\ArrayList;
use Utopia\Audit\Audit;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;

$oauthDefaultSuccess = App::getEnv('_APP_HOME').'/auth/oauth2/success';
$oauthDefaultFailure = App::getEnv('_APP_HOME').'/auth/oauth2/failure';

App::post('/v1/account')
    ->desc('Create Account')
    ->groups(['api', 'account', 'auth'])
    ->label('event', 'account.create')
    ->label('scope', 'public')
    ->label('auth.type', 'emailPassword')
    ->label('sdk.auth', [])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'create')
    ->label('sdk.description', '/docs/references/account/create.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->label('abuse-limit', 10)
    ->param('email', '', new Email(), 'User email.')
    ->param('password', '', new Password(), 'User password. Must be between 6 to 32 chars.')
    ->param('name', '', new Text(128), 'User name. Max length: 128 chars.', true)
    ->inject('request')
    ->inject('response')
    ->inject('project')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($email, $password, $name, $request, $response, $project, $dbForInternal, $audits) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $email = \strtolower($email);
        if ('console' === $project->getId()) {
            $whitelistEmails = $project->getAttribute('authWhitelistEmails');
            $whitelistIPs = $project->getAttribute('authWhitelistIPs');

            if (!empty($whitelistEmails) && !\in_array($email, $whitelistEmails)) {
                throw new Exception('Console registration is restricted to specific emails. Contact your administrator for more information.', 401);
            }

            if (!empty($whitelistIPs) && !\in_array($request->getIP(), $whitelistIPs)) {
                throw new Exception('Console registration is restricted to specific IPs. Contact your administrator for more information.', 401);
            }
        }

        $limit = $project->getAttribute('auths', [])['limit'] ?? 0;

        if ($limit !== 0) {
            $sum = $dbForInternal->count('users', [], APP_LIMIT_USERS);

            if($sum >= $limit) {
                throw new Exception('Project registration is restricted. Contact your administrator for more information.', 501);
            }
        }

        Authorization::disable();

        try {
            $userId = $dbForInternal->getId();
            $user = $dbForInternal->createDocument('users', new Document([
                '$id' => $userId,
                '$read' => ['role:all'],
                '$write' => ['user:'.$userId],
                'email' => $email,
                'emailVerification' => false,
                'status' => true,
                'password' => Auth::passwordHash($password),
                'passwordUpdate' => \time(),
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

        Authorization::unsetRole('role:'.Auth::USER_ROLE_GUEST);
        Authorization::setRole('user:'.$user->getId());
        Authorization::setRole('role:'.Auth::USER_ROLE_MEMBER);

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.create')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($user, Response::MODEL_USER);
    });

App::post('/v1/account/sessions')
    ->desc('Create Account Session')
    ->groups(['api', 'account', 'auth'])
    ->label('event', 'account.sessions.create')
    ->label('scope', 'public')
    ->label('auth.type', 'emailPassword')
    ->label('sdk.auth', [])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createSession')
    ->label('sdk.description', '/docs/references/account/create-session.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_SESSION)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},email:{param-email}')
    ->param('email', '', new Email(), 'User email.')
    ->param('password', '', new Password(), 'User password. Must be between 6 to 32 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('dbForInternal')
    ->inject('locale')
    ->inject('geodb')
    ->inject('audits')
    ->action(function ($email, $password, $request, $response, $dbForInternal, $locale, $geodb, $audits) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Utopia\Locale\Locale $locale */
        /** @var MaxMind\Db\Reader $geodb */
        /** @var Appwrite\Event\Event $audits */

        $email = \strtolower($email);
        $protocol = $request->getProtocol();
        
        $profile = $dbForInternal->findOne('users', [new Query('email', Query::TYPE_EQUAL, [$email])]); // Get user by email address

        if (!$profile || !Auth::passwordVerify($password, $profile->getAttribute('password'))) {
            $audits
                //->setParam('userId', $profile->getId())
                ->setParam('event', 'account.sesssions.failed')
                ->setParam('resource', 'users/'.($profile ? $profile->getId() : ''))
            ;

            throw new Exception('Invalid credentials', 401); // Wrong password or username
        }

        if (false === $profile->getAttribute('status')) { // Account is blocked
            throw new Exception('Invalid credentials. User is blocked', 401); // User is in status blocked
        }

        $detector = new Detector($request->getUserAgent('UNKNOWN'));
        $record = $geodb->get($request->getIP());
        $expiry = \time() + Auth::TOKEN_EXPIRATION_LOGIN_LONG;
        $secret = Auth::tokenGenerator();
        $session = new Document(array_merge(
            [
                '$id' => $dbForInternal->getId(),
                'userId' => $profile->getId(),
                'provider' => Auth::SESSION_PROVIDER_EMAIL,
                'providerUid' => $email,
                'secret' => Auth::hash($secret), // One way hash encryption to protect DB leak
                'expire' => $expiry,
                'userAgent' => $request->getUserAgent('UNKNOWN'),
                'ip' => $request->getIP(),
                'countryCode' => ($record) ? \strtolower($record['country']['iso_code']) : '--',
            ], $detector->getOS(), $detector->getClient(), $detector->getDevice()
        ));

        Authorization::setRole('user:'.$profile->getId());

        $session = $dbForInternal->createDocument('sessions', $session
            ->setAttribute('$read', ['user:'.$profile->getId()])
            ->setAttribute('$write', ['user:'.$profile->getId()])
        );

        $profile->setAttribute('sessions', $session, Document::SET_TYPE_APPEND);
        $profile = $dbForInternal->updateDocument('users', $profile->getId(), $profile);

        $audits
            ->setParam('userId', $profile->getId())
            ->setParam('event', 'account.sessions.create')
            ->setParam('resource', 'users/'.$profile->getId())
        ;

        if (!Config::getParam('domainVerification')) {
            $response
                ->addHeader('X-Fallback-Cookies', \json_encode([Auth::$cookieName => Auth::encodeSession($profile->getId(), $secret)]))
            ;
        }
        
        $response
            ->addCookie(Auth::$cookieName.'_legacy', Auth::encodeSession($profile->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
            ->addCookie(Auth::$cookieName, Auth::encodeSession($profile->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
            ->setStatusCode(Response::STATUS_CODE_CREATED)
        ;

        $countryName = $locale->getText('countries.'.strtolower($session->getAttribute('countryCode')), $locale->getText('locale.country.unknown'));

        $session
            ->setAttribute('current', true)
            ->setAttribute('countryName', $countryName)
        ;
        
        $response->dynamic($session, Response::MODEL_SESSION);
    });

App::get('/v1/account/sessions/oauth2/:provider')
    ->desc('Create Account Session with OAuth2')
    ->groups(['api', 'account'])
    ->label('error', __DIR__.'/../../views/general/error.phtml')
    ->label('scope', 'public')
    ->label('sdk.auth', [])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createOAuth2Session')
    ->label('sdk.description', '/docs/references/account/create-session-oauth2.md')
    ->label('sdk.response.code', Response::STATUS_CODE_MOVED_PERMANENTLY)
    ->label('sdk.response.type', Response::CONTENT_TYPE_HTML)
    ->label('sdk.methodType', 'webAuth')
    ->label('abuse-limit', 50)
    ->label('abuse-key', 'ip:{ip}')
    ->param('provider', '', new WhiteList(\array_keys(Config::getParam('providers')), true), 'OAuth2 Provider. Currently, supported providers are: ' . \implode(', ', \array_keys(\array_filter(Config::getParam('providers'), function($node) {return (!$node['mock']);}))).'.')
    ->param('success', $oauthDefaultSuccess, function ($clients) { return new Host($clients); }, 'URL to redirect back to your app after a successful login attempt.  Only URLs from hostnames in your project platform list are allowed. This requirement helps to prevent an [open redirect](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html) attack against your project API.', true, ['clients'])
    ->param('failure', $oauthDefaultFailure, function ($clients) { return new Host($clients); }, 'URL to redirect back to your app after a failed login attempt.  Only URLs from hostnames in your project platform list are allowed. This requirement helps to prevent an [open redirect](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html) attack against your project API.', true, ['clients'])
    ->param('scopes', [], new ArrayList(new Text(128)), 'A list of custom OAuth2 scopes. Check each provider internal docs for a list of supported scopes.', true)
    ->inject('request')
    ->inject('response')
    ->inject('project')
    ->action(function ($provider, $success, $failure, $scopes, $request, $response, $project) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $project */

        $protocol = $request->getProtocol();
        $callback = $protocol.'://'.$request->getHostname().'/v1/account/sessions/oauth2/callback/'.$provider.'/'.$project->getId();
        $appId = $project->getAttribute('providers', [])[$provider.'Appid'] ?? '';
        $appSecret = $project->getAttribute('providers', [])[$provider.'Secret'] ?? '{}';

        if (!empty($appSecret) && isset($appSecret['version'])) {
            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$appSecret['version']);
            $appSecret = OpenSSL::decrypt($appSecret['data'], $appSecret['method'], $key, 0, \hex2bin($appSecret['iv']), \hex2bin($appSecret['tag']));
        }

        if (empty($appId) || empty($appSecret)) {
            throw new Exception('This provider is disabled. Please configure the provider app ID and app secret key from your '.APP_NAME.' console to continue.', 412);
        }

        $classname = 'Appwrite\\Auth\\OAuth2\\'.\ucfirst($provider);

        if (!\class_exists($classname)) {
            throw new Exception('Provider is not supported', 501);
        }

        $oauth2 = new $classname($appId, $appSecret, $callback, ['success' => $success, 'failure' => $failure], $scopes);

        $response
            ->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->addHeader('Pragma', 'no-cache')
            ->redirect($oauth2->getLoginURL());
    });

App::get('/v1/account/sessions/oauth2/callback/:provider/:projectId')
    ->desc('OAuth2 Callback')
    ->groups(['api', 'account'])
    ->label('error', __DIR__.'/../../views/general/error.phtml')
    ->label('scope', 'public')
    ->label('docs', false)
    ->param('projectId', '', new Text(1024), 'Project unique ID.')
    ->param('provider', '', new WhiteList(\array_keys(Config::getParam('providers')), true), 'OAuth2 provider.')
    ->param('code', '', new Text(1024), 'OAuth2 code.')
    ->param('state', '', new Text(2048), 'Login state params.', true)
    ->inject('request')
    ->inject('response')
    ->action(function ($projectId, $provider, $code, $state, $request, $response) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */

        $domain = $request->getHostname();
        $protocol = $request->getProtocol();
        
        $response
            ->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->addHeader('Pragma', 'no-cache')
            ->redirect($protocol.'://'.$domain.'/v1/account/sessions/oauth2/'.$provider.'/redirect?'
                .\http_build_query(['project' => $projectId, 'code' => $code, 'state' => $state]));
    });

App::post('/v1/account/sessions/oauth2/callback/:provider/:projectId')
    ->desc('OAuth2 Callback')
    ->groups(['api', 'account'])
    ->label('error', __DIR__.'/../../views/general/error.phtml')
    ->label('scope', 'public')
    ->label('origin', '*')
    ->label('docs', false)
    ->param('projectId', '', new Text(1024), 'Project unique ID.')
    ->param('provider', '', new WhiteList(\array_keys(Config::getParam('providers')), true), 'OAuth2 provider.')
    ->param('code', '', new Text(1024), 'OAuth2 code.')
    ->param('state', '', new Text(2048), 'Login state params.', true)
    ->inject('request')
    ->inject('response')
    ->action(function ($projectId, $provider, $code, $state, $request, $response) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */

        $domain = $request->getHostname();
        $protocol = $request->getProtocol();
        
        $response
            ->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->addHeader('Pragma', 'no-cache')
            ->redirect($protocol.'://'.$domain.'/v1/account/sessions/oauth2/'.$provider.'/redirect?'
                .\http_build_query(['project' => $projectId, 'code' => $code, 'state' => $state]));
    });

App::get('/v1/account/sessions/oauth2/:provider/redirect')
    ->desc('OAuth2 Redirect')
    ->groups(['api', 'account'])
    ->label('error', __DIR__.'/../../views/general/error.phtml')
    ->label('event', 'account.sessions.create')
    ->label('scope', 'public')
    ->label('abuse-limit', 50)
    ->label('abuse-key', 'ip:{ip}')
    ->label('docs', false)
    ->param('provider', '', new WhiteList(\array_keys(Config::getParam('providers')), true), 'OAuth2 provider.')
    ->param('code', '', new Text(1024), 'OAuth2 code.')
    ->param('state', '', new Text(2048), 'OAuth2 state params.', true)
    ->inject('request')
    ->inject('response')
    ->inject('project')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('geodb')
    ->inject('audits')
    ->inject('events')
    ->action(function ($provider, $code, $state, $request, $response, $project, $user, $dbForInternal, $geodb, $audits, $events) use ($oauthDefaultSuccess) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var MaxMind\Db\Reader $geodb */
        /** @var Appwrite\Event\Event $audits */
        
        $protocol = $request->getProtocol();
        $callback = $protocol.'://'.$request->getHostname().'/v1/account/sessions/oauth2/callback/'.$provider.'/'.$project->getId();
        $defaultState = ['success' => $project->getAttribute('url', ''), 'failure' => ''];
        $validateURL = new URL();

        $appId = $project->getAttribute('providers', [])[$provider.'Appid'] ?? '';
        $appSecret = $project->getAttribute('providers', [])[$provider.'Secret'] ?? '{}';

        if (!empty($appSecret) && isset($appSecret['version'])) {
            $key = App::getEnv('_APP_OPENSSL_KEY_V'.$appSecret['version']);
            $appSecret = OpenSSL::decrypt($appSecret['data'], $appSecret['method'], $key, 0, \hex2bin($appSecret['iv']), \hex2bin($appSecret['tag']));
        }

        $classname = 'Appwrite\\Auth\\OAuth2\\'.\ucfirst($provider);

        if (!\class_exists($classname)) {
            throw new Exception('Provider is not supported', 501);
        }

        $oauth2 = new $classname($appId, $appSecret, $callback);

        if (!empty($state)) {
            try {
                $state = \array_merge($defaultState, $oauth2->parseState($state));
            } catch (\Exception $exception) {
                throw new Exception('Failed to parse login state params as passed from OAuth2 provider');
            }
        } else {
            $state = $defaultState;
        }

        if (!$validateURL->isValid($state['success'])) {
            throw new Exception('Invalid redirect URL for success login', 400);
        }

        if (!empty($state['failure']) && !$validateURL->isValid($state['failure'])) {
            throw new Exception('Invalid redirect URL for failure login', 400);
        }
        
        $state['failure'] = null;
        $accessToken = $oauth2->getAccessToken($code);

        if (empty($accessToken)) {
            if (!empty($state['failure'])) {
                $response->redirect($state['failure'], 301, 0);
            }

            throw new Exception('Failed to obtain access token');
        }

        $oauth2ID = $oauth2->getUserID($accessToken);
        
        if (empty($oauth2ID)) {
            if (!empty($state['failure'])) {
                $response->redirect($state['failure'], 301, 0);
            }

            throw new Exception('Missing ID from OAuth2 provider', 400);
        }

        $sessions = $user->getAttribute('sessions', []);
        $current = Auth::sessionVerify($sessions, Auth::$secret);

        if($current) { // Delete current session of new one.
            foreach ($sessions as $key => $session) { /** @var Document $session */
                if ($current === $session['$id']) {
                    unset($sessions[$key]);
                    
                    $dbForInternal->deleteDocument('sessions', $session->getId());
                    $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('sessions', $sessions));
                }
            }
        }

        $user = ($user->isEmpty()) ? $dbForInternal->findOne('sessions', [ // Get user by provider id
            new Query('provider', QUERY::TYPE_EQUAL, [$provider]),
            new Query('providerUid', QUERY::TYPE_EQUAL, [$oauth2ID]),
        ]) : $user;

        if ($user === false || $user->isEmpty()) { // No user logged in or with OAuth2 provider ID, create new one or connect with account with same email
            $name = $oauth2->getUserName($accessToken);
            $email = $oauth2->getUserEmail($accessToken);

            $user = $dbForInternal->findOne('users', [new Query('email', Query::TYPE_EQUAL, [$email])]); // Get user by email address

            if ($user === false || $user->isEmpty()) { // Last option -> create the user, generate random password
                $limit = $project->getAttribute('auths', [])['limit'] ?? 0;
        
                if ($limit !== 0) {
                    $sum = $dbForInternal->count('users', [], APP_LIMIT_COUNT);

                    if($sum >= $limit) {
                        throw new Exception('Project registration is restricted. Contact your administrator for more information.', 501);
                    }
                }
                
                Authorization::disable();

                try {
                    $userId = $dbForInternal->getId();
                    $user = $dbForInternal->createDocument('users', new Document([
                        '$id' => $userId,
                        '$read' => ['role:all'],
                        '$write' => ['user:'.$userId],
                        'email' => $email,
                        'emailVerification' => true,
                        'status' => true, // Email should already be authenticated by OAuth2 provider
                        'password' => Auth::passwordHash(Auth::passwordGenerator()),
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
        }

        if (false === $user->getAttribute('status')) { // Account is blocked
            throw new Exception('Invalid credentials. User is blocked', 401); // User is in status blocked
        }

        // Create session token, verify user account and update OAuth2 ID and Access Token

        $detector = new Detector($request->getUserAgent('UNKNOWN'));
        $record = $geodb->get($request->getIP());
        $secret = Auth::tokenGenerator();
        $expiry = \time() + Auth::TOKEN_EXPIRATION_LOGIN_LONG;
        $session = new Document(array_merge([
            '$id' => $dbForInternal->getId(),
            'userId' => $user->getId(),
            'provider' => $provider,
            'providerUid' => $oauth2ID,
            'providerToken' => $accessToken,
            'secret' => Auth::hash($secret), // One way hash encryption to protect DB leak
            'expire' => $expiry,
            'userAgent' => $request->getUserAgent('UNKNOWN'),
            'ip' => $request->getIP(),
            'countryCode' => ($record) ? \strtolower($record['country']['iso_code']) : '--',
        ], $detector->getOS(), $detector->getClient(), $detector->getDevice()));

        $isAnonymousUser = is_null($user->getAttribute('email')) && is_null($user->getAttribute('password'));

        if ($isAnonymousUser) {
            $user
                ->setAttribute('name', $oauth2->getUserName($accessToken))
                ->setAttribute('email', $oauth2->getUserEmail($accessToken))
            ;
        }

        $user
            ->setAttribute('status', true)
            ->setAttribute('sessions', $session, Document::SET_TYPE_APPEND)
        ;

        Authorization::setRole('user:'.$user->getId());

        $session = $dbForInternal->createDocument('sessions', $session
            ->setAttribute('$read', ['user:'.$user->getId()])
            ->setAttribute('$write', ['user:'.$user->getId()])
        );

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user);

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.sessions.create')
            ->setParam('resource', 'users/'.$user->getId())
            ->setParam('data', ['provider' => $provider])
        ;

        $events->setParam('eventData', $response->output($session, Response::MODEL_SESSION));

        if (!Config::getParam('domainVerification')) {
            $response
                ->addHeader('X-Fallback-Cookies', \json_encode([Auth::$cookieName => Auth::encodeSession($user->getId(), $secret)]))
            ;
        }
        
        // Add keys for non-web platforms - TODO - add verification phase to aviod session sniffing
        if (parse_url($state['success'], PHP_URL_PATH) === parse_url($oauthDefaultSuccess, PHP_URL_PATH)) {
            $state['success'] = URLParser::parse($state['success']);
            $query = URLParser::parseQuery($state['success']['query']);
            $query['project'] = $project->getId();
            $query['domain'] = Config::getParam('cookieDomain');
            $query['key'] = Auth::$cookieName;
            $query['secret'] = Auth::encodeSession($user->getId(), $secret);
            $state['success']['query'] = URLParser::unparseQuery($query);
            $state['success'] = URLParser::unparse($state['success']);
        }

        $response
            ->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->addHeader('Pragma', 'no-cache')
            ->addCookie(Auth::$cookieName.'_legacy', Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
            ->addCookie(Auth::$cookieName, Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
            ->redirect($state['success'])
        ;
    });

App::post('/v1/account/sessions/anonymous')
    ->desc('Create Anonymous Session')
    ->groups(['api', 'account', 'auth'])
    ->label('event', 'account.sessions.create')
    ->label('scope', 'public')
    ->label('auth.type', 'anonymous')
    ->label('sdk.auth', [])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createAnonymousSession')
    ->label('sdk.description', '/docs/references/account/create-session-anonymous.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_SESSION)
    ->label('abuse-limit', 50)
    ->label('abuse-key', 'ip:{ip}')
    ->inject('request')
    ->inject('response')
    ->inject('locale')
    ->inject('user')
    ->inject('project')
    ->inject('dbForInternal')
    ->inject('geodb')
    ->inject('audits')
    ->action(function ($request, $response, $locale, $user, $project, $dbForInternal, $geodb, $audits) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var MaxMind\Db\Reader $geodb */
        /** @var Appwrite\Event\Event $audits */

        $protocol = $request->getProtocol();

        if ('console' === $project->getId()) {
            throw new Exception('Failed to create anonymous user.', 401);
        }

        if (!$user->isEmpty()) {
            throw new Exception('Cannot create an anonymous user when logged in.', 401);
        }

        $limit = $project->getAttribute('auths', [])['limit'] ?? 0;

        if ($limit !== 0) {
            $sum = $dbForInternal->count('users', [], APP_LIMIT_COUNT);

            if($sum >= $limit) {
                throw new Exception('Project registration is restricted. Contact your administrator for more information.', 501);
            }
        }

        Authorization::disable();

        $userId = $dbForInternal->getId();
        $user = $dbForInternal->createDocument('users', new Document([
            '$id' => $userId,
            '$read' => ['role:all'], 
            '$write' => ['user:'.$userId],
            'email' => null,
            'emailVerification' => false,
            'status' => true,
            'password' => null,
            'passwordUpdate' => \time(),
            'registration' => \time(),
            'reset' => false,
            'name' => null,
            'prefs' => [],
            'sessions' => [],
            'tokens' => [],
            'memberships' => [],
        ]));

        Authorization::reset();

        // Create session token

        $detector = new Detector($request->getUserAgent('UNKNOWN'));
        $record = $geodb->get($request->getIP());
        $secret = Auth::tokenGenerator();
        $expiry = \time() + Auth::TOKEN_EXPIRATION_LOGIN_LONG;
        $session = new Document(array_merge(
            [
                '$id' => $dbForInternal->getId(),
                'userId' => $user->getId(),
                'provider' => Auth::SESSION_PROVIDER_ANONYMOUS,
                'secret' => Auth::hash($secret), // One way hash encryption to protect DB leak
                'expire' => $expiry,
                'userAgent' => $request->getUserAgent('UNKNOWN'),
                'ip' => $request->getIP(),
                'countryCode' => ($record) ? \strtolower($record['country']['iso_code']) : '--',
            ],
            $detector->getOS(),
            $detector->getClient(),
            $detector->getDevice()
        ));

        Authorization::setRole('user:'.$user->getId());

        $session = $dbForInternal->createDocument('sessions', $session
            ->setAttribute('$read', ['user:'.$user->getId()])
            ->setAttribute('$write', ['user:'.$user->getId()])
        );

        $user = $dbForInternal->updateDocument('users', $user->getId(),
            $user->setAttribute('sessions', $session, Document::SET_TYPE_APPEND));

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.sessions.create')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        if (!Config::getParam('domainVerification')) {
            $response
                ->addHeader('X-Fallback-Cookies', \json_encode([Auth::$cookieName => Auth::encodeSession($user->getId(), $secret)]))
            ;
        }

        $response
            ->addCookie(Auth::$cookieName.'_legacy', Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
            ->addCookie(Auth::$cookieName, Auth::encodeSession($user->getId(), $secret), $expiry, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
            ->setStatusCode(Response::STATUS_CODE_CREATED)
        ;

        $countryName = (isset($countries[strtoupper($session->getAttribute('countryCode'))]))
        ? $countries[strtoupper($session->getAttribute('countryCode'))]
        : $locale->getText('locale.country.unknown');

        $session
            ->setAttribute('current', true)
            ->setAttribute('countryName', $countryName)
        ;

        $response->dynamic($session, Response::MODEL_SESSION);
    });

App::post('/v1/account/jwt')
    ->desc('Create Account JWT')
    ->groups(['api', 'account', 'auth'])
    ->label('scope', 'account')
    ->label('auth.type', 'jwt')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createJWT')
    ->label('sdk.description', '/docs/references/account/create-jwt.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_JWT)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},userId:{userId}')
    ->inject('response')
    ->inject('user')
    ->action(function ($response, $user) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
            
        $sessions = $user->getAttribute('sessions', []);
        $current = new Document();

        foreach ($sessions as $session) { 
            /** @var Utopia\Database\Document $session */

            if ($session->getAttribute('secret') == Auth::hash(Auth::$secret)) { // If current session delete the cookies too
                $current = $session;
            }
        }

        if($current->isEmpty()) {
            throw new Exception('No valid session found', 401);
        }
        
        $jwt = new JWT(App::getEnv('_APP_OPENSSL_KEY_V1'), 'HS256', 900, 10); // Instantiate with key, algo, maxAge and leeway.

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic(new Document(['jwt' => $jwt->encode([
            // 'uid'    => 1,
            // 'aud'    => 'http://site.com',
            // 'scopes' => ['user'],
            // 'iss'    => 'http://api.mysite.com',
            'userId' => $user->getId(),
            'sessionId' => $current->getId(),
        ])]), Response::MODEL_JWT);
    });

App::get('/v1/account')
    ->desc('Get Account')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'get')
    ->label('sdk.description', '/docs/references/account/get.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->inject('response')
    ->inject('user')
    ->action(function ($response, $user) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */

        $response->dynamic($user, Response::MODEL_USER);
    });

App::get('/v1/account/prefs')
    ->desc('Get Account Preferences')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'getPrefs')
    ->label('sdk.description', '/docs/references/account/get-prefs.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_PREFERENCES)
    ->inject('response')
    ->inject('user')
    ->action(function ($response, $user) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */

        $prefs = $user->getAttribute('prefs', new \stdClass());

        $response->dynamic(new Document($prefs), Response::MODEL_PREFERENCES);
    });

App::get('/v1/account/sessions')
    ->desc('Get Account Sessions')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'getSessions')
    ->label('sdk.description', '/docs/references/account/get-sessions.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_SESSION_LIST)
    ->inject('response')
    ->inject('user')
    ->inject('locale')
    ->action(function ($response, $user, $locale) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Locale\Locale $locale */

        $sessions = $user->getAttribute('sessions', []);
        $current = Auth::sessionVerify($sessions, Auth::$secret);

        foreach ($sessions as $key => $session) { /** @var Document $session */
            $countryName = $locale->getText('countries.'.strtolower($session->getAttribute('countryCode')), $locale->getText('locale.country.unknown'));

            $session->setAttribute('countryName', $countryName);
            $session->setAttribute('current', ($current == $session->getId()) ? true : false);

            $sessions[$key] = $session;
        }

        $response->dynamic(new Document([
            'sessions' => $sessions,
            'sum' => count($sessions),
        ]), Response::MODEL_SESSION_LIST);
    });

App::get('/v1/account/logs')
    ->desc('Get Account Logs')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'getLogs')
    ->label('sdk.description', '/docs/references/account/get-logs.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_LOG_LIST)
    ->inject('response')
    ->inject('user')
    ->inject('locale')
    ->inject('geodb')
    ->inject('dbForInternal')
    ->action(function ($response, $user, $locale, $geodb, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Locale\Locale $locale */
        /** @var MaxMind\Db\Reader $geodb */
        /** @var Utopia\Database\Database $dbForInternal */

        $audit = new Audit($dbForInternal);

        $logs = $audit->getLogsByUserAndEvents($user->getId(), [
            'account.create',
            'account.delete',
            'account.update.name',
            'account.update.email',
            'account.update.password',
            'account.update.prefs',
            'account.sessions.create',
            'account.sessions.delete',
            'account.recovery.create',
            'account.recovery.update',
            'account.verification.create',
            'account.verification.update',
            'teams.membership.create',
            'teams.membership.update',
            'teams.membership.delete',
        ]);

        $output = [];

        foreach ($logs as $i => &$log) {
            $log['userAgent'] = (!empty($log['userAgent'])) ? $log['userAgent'] : 'UNKNOWN';

            $detector = new Detector($log['userAgent']);

            $output[$i] = new Document(array_merge([
                'event' => $log['event'],
                'ip' => $log['ip'],
                'time' => $log['time'],
            ], $detector->getOS(), $detector->getClient(), $detector->getDevice()));

            $record = $geodb->get($log['ip']);

            if ($record) {
                $output[$i]['countryCode'] = $locale->getText('countries.'.strtolower($record['country']['iso_code']), false) ? \strtolower($record['country']['iso_code']) : '--';
                $output[$i]['countryName'] = $locale->getText('countries.'.strtolower($record['country']['iso_code']), $locale->getText('locale.country.unknown'));        
            } else {
                $output[$i]['countryCode'] = '--';
                $output[$i]['countryName'] = $locale->getText('locale.country.unknown');
            }

        }

        $response->dynamic(new Document(['logs' => $output]), Response::MODEL_LOG_LIST);
    });

App::get('/v1/account/sessions/:sessionId')
    ->desc('Get Session By ID')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'getSession')
    ->label('sdk.description', '/docs/references/account/get-session.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_SESSION)
    ->param('sessionId', null, new UID(), 'Session unique ID. Use the string \'current\' to get the current device session.')
    ->inject('response')
    ->inject('user')
    ->inject('locale')
    ->inject('dbForInternal')
    ->action(function ($sessionId, $response, $user, $locale, $dbForInternal) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Utopia\Database\Database $dbForInternal */

        $sessions   = $user->getAttribute('sessions', []);
        $sessionId  = ($sessionId === 'current')
            ? Auth::sessionVerify($user->getAttribute('sessions'), Auth::$secret)
            : $sessionId;

        foreach ($sessions as $session) { /** @var Document $session */
            if ($sessionId == $session->getId()) {

                $countryName = (isset($countries[strtoupper($session->getAttribute('countryCode'))]))
                    ? $countries[strtoupper($session->getAttribute('countryCode'))]
                    : $locale->getText('locale.country.unknown');

                $session
                    ->setAttribute('current', ($session->getAttribute('secret') == Auth::hash(Auth::$secret)))
                    ->setAttribute('countryName', $countryName)
                ;
                    
                return $response->dynamic($session, Response::MODEL_SESSION);
            }
        }

        throw new Exception('Session not found', 404);
    });

App::patch('/v1/account/name')
    ->desc('Update Account Name')
    ->groups(['api', 'account'])
    ->label('event', 'account.update.name')
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updateName')
    ->label('sdk.description', '/docs/references/account/update-name.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->param('name', '', new Text(128), 'User name. Max length: 128 chars.')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($name, $response, $user, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('name', $name));

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.update.name')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->dynamic($user, Response::MODEL_USER);
    });

App::patch('/v1/account/password')
    ->desc('Update Account Password')
    ->groups(['api', 'account'])
    ->label('event', 'account.update.password')
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updatePassword')
    ->label('sdk.description', '/docs/references/account/update-password.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->param('password', '', new Password(), 'New user password. Must be between 6 to 32 chars.')
    ->param('oldPassword', '', new Password(), 'Old user password. Must be between 6 to 32 chars.', true)
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($password, $oldPassword, $response, $user, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        // Check old password only if its an existing user.
        if ($user->getAttribute('passwordUpdate') !== 0 && !Auth::passwordVerify($oldPassword, $user->getAttribute('password'))) { // Double check user password
            throw new Exception('Invalid credentials', 401);
        }

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user
            ->setAttribute('password', Auth::passwordHash($password))
            ->setAttribute('passwordUpdate', \time())
        );

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.update.password')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->dynamic($user, Response::MODEL_USER);
    });

App::patch('/v1/account/email')
    ->desc('Update Account Email')
    ->groups(['api', 'account'])
    ->label('event', 'account.update.email')
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updateEmail')
    ->label('sdk.description', '/docs/references/account/update-email.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->param('email', '', new Email(), 'User email.')
    ->param('password', '', new Password(), 'User password. Must be between 6 to 32 chars.')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($email, $password, $response, $user, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $isAnonymousUser = is_null($user->getAttribute('email')) && is_null($user->getAttribute('password')); // Check if request is from an anonymous account for converting

        if (
            !$isAnonymousUser &&
            !Auth::passwordVerify($password, $user->getAttribute('password'))
        ) { // Double check user password
            throw new Exception('Invalid credentials', 401);
        }

        $email = \strtolower($email);
        $profile = $dbForInternal->findOne('users', [new Query('email', Query::TYPE_EQUAL, [\strtolower($email)])]); // Get user by email address

        if ($profile) {
            throw new Exception('User already registered', 400);
        }

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user
            ->setAttribute('password', $isAnonymousUser ? Auth::passwordHash($password) : $user->getAttribute('password', ''))
            ->setAttribute('email', $email)
            ->setAttribute('emailVerification', false) // After this user needs to confirm mail again
        );

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.update.email')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->dynamic($user, Response::MODEL_USER);
    });

App::patch('/v1/account/prefs')
    ->desc('Update Account Preferences')
    ->groups(['api', 'account'])
    ->label('event', 'account.update.prefs')
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updatePrefs')
    ->label('sdk.description', '/docs/references/account/update-prefs.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_USER)
    ->param('prefs', [], new Assoc(), 'Prefs key-value JSON object.')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($prefs, $response, $user, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('prefs', $prefs));

        $audits
            ->setParam('event', 'account.update.prefs')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->dynamic($user, Response::MODEL_USER);
    });

App::delete('/v1/account')
    ->desc('Delete Account')
    ->groups(['api', 'account'])
    ->label('event', 'account.delete')
    ->label('scope', 'account')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'delete')
    ->label('sdk.description', '/docs/references/account/delete.md')
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->inject('request')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->inject('events')
    ->action(function ($request, $response, $user, $dbForInternal, $audits, $events) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */

        $protocol = $request->getProtocol();
        $user = $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('status', false));

        // TODO delete all tokens or only current session?
        // TODO delete all user data according to GDPR. Make sure everything is backed up and backups are deleted later
        /*
         * Data to delete
         * * Tokens
         * * Memberships
         */

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.delete')
            ->setParam('resource', 'users/'.$user->getId())
            ->setParam('data', $user->getArrayCopy())
        ;

        $events
            ->setParam('eventData', $response->output($user, Response::MODEL_USER))
        ;

        if (!Config::getParam('domainVerification')) {
            $response
                ->addHeader('X-Fallback-Cookies', \json_encode([]))
            ;
        }

        $response
            ->addCookie(Auth::$cookieName.'_legacy', '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
            ->addCookie(Auth::$cookieName, '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
            ->noContent()
        ;
    });

App::delete('/v1/account/sessions/:sessionId')
    ->desc('Delete Account Session')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('event', 'account.sessions.delete')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'deleteSession')
    ->label('sdk.description', '/docs/references/account/delete-session.md')
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->label('abuse-limit', 100)
    ->param('sessionId', null, new UID(), 'Session unique ID. Use the string \'current\' to delete the current device session.')
    ->inject('request')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('locale')
    ->inject('audits')
    ->inject('events')
    ->action(function ($sessionId, $request, $response, $user, $dbForInternal, $locale, $audits, $events) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */

        $protocol = $request->getProtocol();
        $sessionId = ($sessionId === 'current')
            ? Auth::sessionVerify($user->getAttribute('sessions'), Auth::$secret)
            : $sessionId;
                
        $sessions = $user->getAttribute('sessions', []);

        foreach ($sessions as $key => $session) { /** @var Document $session */
            if ($sessionId == $session->getId()) {
                unset($sessions[$key]);

                $dbForInternal->deleteDocument('sessions', $session->getId());

                $audits
                    ->setParam('userId', $user->getId())
                    ->setParam('event', 'account.sessions.delete')
                    ->setParam('resource', '/user/'.$user->getId())
                ;

                $session->setAttribute('current', false);
                
                if ($session->getAttribute('secret') == Auth::hash(Auth::$secret)) { // If current session delete the cookies too
                    $session
                        ->setAttribute('current', true)
                        ->setAttribute('countryName', (isset($countries[strtoupper($session->getAttribute('countryCode'))])) ? $countries[strtoupper($session->getAttribute('countryCode'))] : $locale->getText('locale.country.unknown'))
                    ;
                    
                    if (!Config::getParam('domainVerification')) {
                        $response
                            ->addHeader('X-Fallback-Cookies', \json_encode([]))
                        ;
                    }

                    $response
                        ->addCookie(Auth::$cookieName.'_legacy', '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
                        ->addCookie(Auth::$cookieName, '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
                    ;
                }

                $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('sessions', $sessions));

                $events
                    ->setParam('eventData', $response->output($session, Response::MODEL_SESSION))
                ;

                return $response->noContent();
            }
        }

        throw new Exception('Session not found', 404);
    });

App::delete('/v1/account/sessions')
    ->desc('Delete All Account Sessions')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('event', 'account.sessions.delete')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'deleteSessions')
    ->label('sdk.description', '/docs/references/account/delete-sessions.md')
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->label('abuse-limit', 100)
    ->inject('request')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('locale')
    ->inject('audits')
    ->inject('events')
    ->action(function ($request, $response, $user, $dbForInternal, $locale, $audits, $events) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */

        $protocol = $request->getProtocol();
        $sessions = $user->getAttribute('sessions', []);

        foreach ($sessions as $session) { /** @var Document $session */
            $dbForInternal->deleteDocument('sessions', $session->getId());

            $audits
                ->setParam('userId', $user->getId())
                ->setParam('event', 'account.sessions.delete')
                ->setParam('resource', '/user/'.$user->getId())
            ;

            if (!Config::getParam('domainVerification')) {
                $response
                    ->addHeader('X-Fallback-Cookies', \json_encode([]))
                ;
            }

            $session
                ->setAttribute('current', false)
                ->setAttribute('countryName', (isset($countries[strtoupper($session->getAttribute('countryCode'))])) ? $countries[strtoupper($session->getAttribute('countryCode'))] : $locale->getText('locale.country.unknown'))
            ;

            if ($session->getAttribute('secret') == Auth::hash(Auth::$secret)) { // If current session delete the cookies too
                $session->setAttribute('current', true);
                $response
                    ->addCookie(Auth::$cookieName.'_legacy', '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, null)
                    ->addCookie(Auth::$cookieName, '', \time() - 3600, '/', Config::getParam('cookieDomain'), ('https' == $protocol), true, Config::getParam('cookieSamesite'))
                ;
            }
        }

        $dbForInternal->updateDocument('users', $user->getId(), $user->setAttribute('sessions', []));
                    
        $events
            ->setParam('eventData', $response->output(new Document([
                'sessions' => $sessions,
                'sum' => count($sessions),
            ]), Response::MODEL_SESSION_LIST))
        ;

        $response->noContent();
    });

App::post('/v1/account/recovery')
    ->desc('Create Password Recovery')
    ->groups(['api', 'account'])
    ->label('scope', 'public')
    ->label('event', 'account.recovery.create')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createRecovery')
    ->label('sdk.description', '/docs/references/account/create-recovery.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TOKEN)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},email:{param-email}')
    ->param('email', '', new Email(), 'User email.')
    ->param('url', '', function ($clients) { return new Host($clients); }, 'URL to redirect the user back to your app from the recovery email. Only URLs from hostnames in your project platform list are allowed. This requirement helps to prevent an [open redirect](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html) attack against your project API.', false, ['clients'])
    ->inject('request')
    ->inject('response')
    ->inject('dbForInternal')
    ->inject('project')
    ->inject('locale')
    ->inject('mails')
    ->inject('audits')
    ->inject('events')
    ->action(function ($email, $url, $request, $response, $dbForInternal, $project, $locale, $mails, $audits, $events) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Appwrite\Event\Event $mails */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */

        $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::$roles);
        $isAppUser = Auth::isAppUser(Authorization::$roles);

        $email = \strtolower($email);
        $profile = $dbForInternal->findOne('users', [new Query('email', Query::TYPE_EQUAL, [$email])]); // Get user by email address

        if (!$profile) {
            throw new Exception('User not found', 404);
        }

        if (false === $profile->getAttribute('status')) { // Account is blocked
            throw new Exception('Invalid credentials. User is blocked', 401);
        }

        $expire = \time() + Auth::TOKEN_EXPIRATION_RECOVERY;

        $secret = Auth::tokenGenerator();
        $recovery = new Document([
            '$id' => $dbForInternal->getId(),
            'userId' => $profile->getId(),
            'type' => Auth::TOKEN_TYPE_RECOVERY,
            'secret' => Auth::hash($secret), // One way hash encryption to protect DB leak
            'expire' => $expire,
            'userAgent' => $request->getUserAgent('UNKNOWN'),
            'ip' => $request->getIP(),
        ]);
            
        Authorization::setRole('user:'.$profile->getId());

        $profile->setAttribute('tokens', $recovery, Document::SET_TYPE_APPEND);

        $profile = $dbForInternal->updateDocument('users', $profile->getId(), $profile);

        $url = Template::parseURL($url);
        $url['query'] = Template::mergeQuery(((isset($url['query'])) ? $url['query'] : ''), ['userId' => $profile->getId(), 'secret' => $secret, 'expire' => $expire]);
        $url = Template::unParseURL($url);

        $mails
            ->setParam('event', 'account.recovery.create')
            ->setParam('from', $project->getId())
            ->setParam('recipient', $profile->getAttribute('email', ''))
            ->setParam('name', $profile->getAttribute('name', ''))
            ->setParam('url', $url)
            ->setParam('locale', $locale->default)
            ->setParam('project', $project->getAttribute('name', ['[APP-NAME]']))
            ->setParam('type', MAIL_TYPE_RECOVERY)
            ->trigger();
        ;

        $events
            ->setParam('eventData',
                $response->output($recovery->setAttribute('secret', $secret),
                Response::MODEL_TOKEN
            ))
        ;

        $recovery  // Hide secret for clients, sp
            ->setAttribute('secret',
                ($isPrivilegedUser || $isAppUser) ? $secret : '');

        $audits
            ->setParam('userId', $profile->getId())
            ->setParam('event', 'account.recovery.create')
            ->setParam('resource', 'users/'.$profile->getId())
        ;

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($recovery, Response::MODEL_TOKEN);
    });

App::put('/v1/account/recovery')
    ->desc('Complete Password Recovery')
    ->groups(['api', 'account'])
    ->label('scope', 'public')
    ->label('event', 'account.recovery.update')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updateRecovery')
    ->label('sdk.description', '/docs/references/account/update-recovery.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TOKEN)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},userId:{param-userId}')
    ->param('userId', '', new UID(), 'User account UID address.')
    ->param('secret', '', new Text(256), 'Valid reset token.')
    ->param('password', '', new Password(), 'New password. Must be between 6 to 32 chars.')
    ->param('passwordAgain', '', new Password(), 'New password again. Must be between 6 to 32 chars.')
    ->inject('response')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($userId, $secret, $password, $passwordAgain, $response, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */
    
        if ($password !== $passwordAgain) {
            throw new Exception('Passwords must match', 400);
        }

        $profile = $dbForInternal->getDocument('users', $userId);

        if ($profile->isEmpty()) {
            throw new Exception('User not found', 404);
        }

        $tokens = $profile->getAttribute('tokens', []);
        $recovery = Auth::tokenVerify($tokens, Auth::TOKEN_TYPE_RECOVERY, $secret);

        if (!$recovery) {
            throw new Exception('Invalid recovery token', 401);
        }

        Authorization::setRole('user:'.$profile->getId());

        $profile = $dbForInternal->updateDocument('users', $profile->getId(), $profile
            ->setAttribute('password', Auth::passwordHash($password))
            ->setAttribute('passwordUpdate', \time())
            ->setAttribute('emailVerification', true)
        );

        /**
         * We act like we're updating and validating
         *  the recovery token but actually we don't need it anymore.
         */

        foreach ($tokens as $key => $token) {
            if($recovery === $token->getId()) {
                $recovery = $token;
                unset($tokens[$key]);
            }
        }

        $dbForInternal->updateDocument('users', $profile->getId(), $profile->setAttribute('tokens', $tokens));

        $audits
            ->setParam('userId', $profile->getId())
            ->setParam('event', 'account.recovery.update')
            ->setParam('resource', 'users/'.$profile->getId())
        ;

        $response->dynamic($recovery, Response::MODEL_TOKEN);
    });

App::post('/v1/account/verification')
    ->desc('Create Email Verification')
    ->groups(['api', 'account'])
    ->label('scope', 'account')
    ->label('event', 'account.verification.create')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'createVerification')
    ->label('sdk.description', '/docs/references/account/create-verification.md')
    ->label('sdk.response.code', Response::STATUS_CODE_CREATED)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TOKEN)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},userId:{userId}')
    ->param('url', '', function ($clients) { return new Host($clients); }, 'URL to redirect the user back to your app from the verification email. Only URLs from hostnames in your project platform list are allowed. This requirement helps to prevent an [open redirect](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html) attack against your project API.', false, ['clients']) // TODO add built-in confirm page
    ->inject('request')
    ->inject('response')
    ->inject('project')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('locale')
    ->inject('audits')
    ->inject('events')
    ->inject('mails')
    ->action(function ($url, $request, $response, $project, $user, $dbForInternal, $locale, $audits, $events, $mails) {
        /** @var Utopia\Swoole\Request $request */
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $project */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Utopia\Locale\Locale $locale */
        /** @var Appwrite\Event\Event $audits */
        /** @var Appwrite\Event\Event $events */
        /** @var Appwrite\Event\Event $mails */

        $isPrivilegedUser = Auth::isPrivilegedUser(Authorization::$roles);
        $isAppUser = Auth::isAppUser(Authorization::$roles);

        $verificationSecret = Auth::tokenGenerator();

        $expire = \time() + Auth::TOKEN_EXPIRATION_CONFIRM;
        
        $verification = new Document([
            '$id' => $dbForInternal->getId(),
            'userId' => $user->getId(),
            'type' => Auth::TOKEN_TYPE_VERIFICATION,
            'secret' => Auth::hash($verificationSecret), // One way hash encryption to protect DB leak
            'expire' => $expire,
            'userAgent' => $request->getUserAgent('UNKNOWN'),
            'ip' => $request->getIP(),
        ]);
            
        Authorization::setRole('user:'.$user->getId());

        $user->setAttribute('tokens', $verification, Document::SET_TYPE_APPEND);

        $user = $dbForInternal->updateDocument('users', $user->getId(), $user);

        $url = Template::parseURL($url);
        $url['query'] = Template::mergeQuery(((isset($url['query'])) ? $url['query'] : ''), ['userId' => $user->getId(), 'secret' => $verificationSecret, 'expire' => $expire]);
        $url = Template::unParseURL($url);

        $mails
            ->setParam('event', 'account.verification.create')
            ->setParam('from', $project->getId())
            ->setParam('recipient', $user->getAttribute('email'))
            ->setParam('name', $user->getAttribute('name'))
            ->setParam('url', $url)
            ->setParam('locale', $locale->default)
            ->setParam('project', $project->getAttribute('name', ['[APP-NAME]']))
            ->setParam('type', MAIL_TYPE_VERIFICATION)
            ->trigger()
        ;

        $events
            ->setParam('eventData',
                $response->output($verification->setAttribute('secret', $verificationSecret),
                Response::MODEL_TOKEN
            ))
        ;

        $verification  // Hide secret for clients, sp
            ->setAttribute('secret',
                ($isPrivilegedUser || $isAppUser) ? $verificationSecret : '');

        $audits
            ->setParam('userId', $user->getId())
            ->setParam('event', 'account.verification.create')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($verification, Response::MODEL_TOKEN);
    });

App::put('/v1/account/verification')
    ->desc('Complete Email Verification')
    ->groups(['api', 'account'])
    ->label('scope', 'public')
    ->label('event', 'account.verification.update')
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'account')
    ->label('sdk.method', 'updateVerification')
    ->label('sdk.description', '/docs/references/account/update-verification.md')
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_TOKEN)
    ->label('abuse-limit', 10)
    ->label('abuse-key', 'url:{url},userId:{param-userId}')
    ->param('userId', '', new UID(), 'User unique ID.')
    ->param('secret', '', new Text(256), 'Valid verification token.')
    ->inject('response')
    ->inject('user')
    ->inject('dbForInternal')
    ->inject('audits')
    ->action(function ($userId, $secret, $response, $user, $dbForInternal, $audits) {
        /** @var Appwrite\Utopia\Response $response */
        /** @var Utopia\Database\Document $user */
        /** @var Utopia\Database\Database $dbForInternal */
        /** @var Appwrite\Event\Event $audits */

        $profile = $dbForInternal->getDocument('users', $userId);

        if ($profile->isEmpty()) {
            throw new Exception('User not found', 404);
        }

        $tokens = $profile->getAttribute('tokens', []);
        $verification = Auth::tokenVerify($tokens, Auth::TOKEN_TYPE_VERIFICATION, $secret);

        if (!$verification) {
            throw new Exception('Invalid verification token', 401);
        }

        Authorization::setRole('user:'.$profile->getId());

        $profile = $dbForInternal->updateDocument('users', $profile->getId(), $profile->setAttribute('emailVerification', true));

        /**
         * We act like we're updating and validating
         *  the verification token but actually we don't need it anymore.
         */
        foreach ($tokens as $key => $token) {
            if($token->getId() === $verification) {
                $verification = $token;
                unset($tokens[$key]);
            }
        }

        $dbForInternal->updateDocument('users', $profile->getId(), $profile->setAttribute('tokens', $tokens));

        $audits
            ->setParam('userId', $profile->getId())
            ->setParam('event', 'account.verification.update')
            ->setParam('resource', 'users/'.$user->getId())
        ;

        $response->dynamic($verification, Response::MODEL_TOKEN);
    });
