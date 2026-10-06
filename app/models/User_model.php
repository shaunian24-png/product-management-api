<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * User_model - reads/writes the `users` table (created by migration 001).
 */
class User_model extends Model
{
    protected $table = 'users';

    /** Columns that are safe to send to the client (never the password hash). */
    private $public_columns = 'id, username, email, role, is_active, created_at';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_auth_columns();
    }

    /**
     * Some legacy installs already created the users table without the auth fields.
     * Add them in-place so the app can still register/login against the existing DB.
     */
    public function ensure_auth_columns()
    {
        $table = $this->table;

        $columns = $this->db->raw("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        $existing = array_fill_keys($columns, true);

        $sql = [];

        if (!isset($existing['password'])) {
            $sql[] = "ALTER TABLE `{$table}` ADD COLUMN `password` VARCHAR(255) NOT NULL";
        }

        if (!isset($existing['role'])) {
            $sql[] = "ALTER TABLE `{$table}` ADD COLUMN `role` ENUM('admin','moderator','user') NOT NULL DEFAULT 'user'";
        }

        if (!isset($existing['is_active'])) {
            $sql[] = "ALTER TABLE `{$table}` ADD COLUMN `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1";
        }

        if (!isset($existing['created_at'])) {
            $sql[] = "ALTER TABLE `{$table}` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        }

        if (!isset($existing['updated_at'])) {
            $sql[] = "ALTER TABLE `{$table}` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL";
        }

        foreach ($sql as $statement) {
            $this->db->raw($statement);
        }
    }

    public function find_by_id($id)
    {
        return $this->db->table($this->table)
            ->select($this->public_columns)
            ->where('id', (int) $id)
            ->get() ?: null;
    }

    /** Look up by e-mail OR username; includes the password hash for login checks. */
    public function find_for_login($login)
    {
        return $this->db->raw(
            "SELECT id, username, email, password, role, is_active, created_at
               FROM {$this->table}
              WHERE email = ? OR username = ?
              LIMIT 1",
            [$login, $login]
        )->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function email_exists($email)
    {
        return $this->db->table($this->table)->where('email', $email)->count() > 0;
    }

    public function username_exists($username)
    {
        return $this->db->table($this->table)->where('username', $username)->count() > 0;
    }

    /** @return int new user id */
    public function create_user($username, $email, $password)
    {
        return $this->db->table($this->table)->insert([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            // role + is_active use the column defaults ('user', 1)
        ]);
    }
}
