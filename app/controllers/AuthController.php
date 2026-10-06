<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * AuthController - token based authentication using the LavaLust API library.
 *
 *   POST /api/auth/register   create an account
 *   POST /api/auth/login      returns access_token (15 min) + refresh_token (7 days)
 *   POST /api/auth/refresh    swap a refresh_token for a new token pair
 *   POST /api/auth/logout     revoke the refresh_token
 *   GET  /api/auth/me         current user (needs "Authorization: Bearer <access_token>")
 */
class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json; charset=utf-8');
        $this->call->library('api');   // loads config/api.php, the database and CORS headers
        $this->call->helper('request');
        $this->call->model('User_model');
    }

    // ------------------------------------------------------------------
    // POST /api/auth/register
    // ------------------------------------------------------------------
    public function register()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('register_' . client_ip(), 10, 60);

        if (filter_var(getenv('ALLOW_REGISTRATION') ?: 'true', FILTER_VALIDATE_BOOLEAN) === false) {
            $this->api->respond_error('Registration is disabled.', 403);
        }

        $input = $this->body_or_fail();

        $username = trim((string) ($input['username'] ?? ''));
        $email    = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        $errors = [];

        if (strlen($username) < 3 || strlen($username) > 100) {
            $errors['username'] = 'Username must be 3-100 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $errors['username'] = 'Username may only contain letters, numbers, dot, dash and underscore.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'A valid email address is required.';
        }

        if (strlen($password) < 8 || strlen($password) > 72) {
            // bcrypt only uses the first 72 bytes
            $errors['password'] = 'Password must be 8-72 characters.';
        }

        if (empty($errors['username']) && $this->User_model->username_exists($username)) {
            $errors['username'] = 'Username is already taken.';
        }
        if (empty($errors['email']) && $this->User_model->email_exists($email)) {
            $errors['email'] = 'Email is already registered.';
        }

        if ($errors) {
            $this->validation_failed($errors);
        }

        $id   = $this->User_model->create_user($username, $email, $password);
        $user = $this->User_model->find_by_id($id);

        $this->api->respond([
            'success' => true,
            'message' => 'Account created. You can now log in.',
            'user'    => $user,
        ], 201);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------
    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('login_' . client_ip(), 10, 60);

        $input    = $this->body_or_fail();
        $login    = trim((string) ($input['email'] ?? $input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->validation_failed([
                'email'    => $login === '' ? 'Email is required.' : null,
                'password' => $password === '' ? 'Password is required.' : null,
            ]);
        }

        $user = $this->User_model->find_for_login($login);

        // Always run password_verify() so the response time does not reveal
        // whether the account exists.
        $hash  = $user['password'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid || (int) $user['is_active'] !== 1) {
            $this->api->respond_error('Invalid email or password.', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id'     => (int) $user['id'],
            'role'   => $user['role'],
            'scopes' => $this->scopes_for_role($user['role']),
        ]);

        unset($user['password']);
        $user['id']        = (int) $user['id'];
        $user['is_active'] = (int) $user['is_active'];

        $this->api->respond([
            'success' => true,
            'message' => 'Login successful.',
            'user'    => $user,
            'tokens'  => $tokens,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/refresh   { "refresh_token": "..." }
    // ------------------------------------------------------------------
    public function refresh()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('refresh_' . client_ip(), 30, 60);

        $input = $this->body_or_fail();
        $token = (string) ($input['refresh_token'] ?? '');

        if ($token === '') {
            $this->validation_failed(['refresh_token' => 'refresh_token is required.']);
        }

        // Responds with { message, tokens } or 403 on its own.
        $this->api->refresh_access_token($token);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/logout   { "refresh_token": "..." }
    // ------------------------------------------------------------------
    public function logout()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('logout_' . client_ip(), 30, 60);

        $input = $this->body_or_fail();
        $token = (string) ($input['refresh_token'] ?? '');

        if ($token !== '') {
            $this->api->revoke_refresh_token($token);
        }

        $this->api->respond(['success' => true, 'message' => 'Logged out.']);
    }

    // ------------------------------------------------------------------
    // GET /api/auth/me
    // ------------------------------------------------------------------
    public function me()
    {
        $this->api->require_method('GET');
        $auth = $this->api->require_jwt();

        $user = $this->User_model->find_by_id($auth['sub']);
        if (!$user) {
            $this->api->respond_error('Unauthorized', 401);
        }

        $this->api->respond(['success' => true, 'user' => $user]);
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------
    private function body_or_fail()
    {
        $input = json_input();
        if ($input === null) {
            $this->api->respond_error('Request body must be valid JSON.', 400);
        }
        return $input;
    }

    private function validation_failed(array $errors)
    {
        $this->api->respond([
            'success' => false,
            'error'   => 'Validation failed.',
            'errors'  => array_filter($errors),
            'status'  => 422,
        ], 422);
    }

    /** Same role -> scope mapping used inside the Api library. */
    private function scopes_for_role($role)
    {
        return [
            'admin'  => ['read', 'write', 'delete'],
            'editor' => ['read', 'write'],
        ][$role] ?? ['read'];
    }
}
