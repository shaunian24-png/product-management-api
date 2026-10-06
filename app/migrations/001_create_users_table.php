<?php

class Create_users_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if (!$this->_lava->dbforge->table_exists('users')) {
            $this->_lava->dbforge
                ->add_field([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => TRUE,
                        'auto_increment' => TRUE,
                        'null'           => FALSE,
                    ],
                    'username' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => FALSE,
                    ],
                    'email' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => FALSE,
                        'unique'     => TRUE,
                    ],
                    'password' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => FALSE,
                    ],
                    'role' => [
                        'type'       => 'ENUM',
                        'constraint' => "'admin','moderator','user'",
                        'null'       => FALSE,
                        'default'    => 'user',
                    ],
                    'is_active' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'unsigned'   => TRUE,
                        'null'       => FALSE,
                        'default'    => 1,
                    ],
                    'created_at' => [
                        'type'    => 'DATETIME',
                        'null'    => FALSE,
                        'default' => 'CURRENT_TIMESTAMP',
                    ],
                    'updated_at' => [
                        'type'    => 'DATETIME',
                        'null'    => TRUE,
                        'default' => NULL,
                    ],
                ])
                ->add_key('id', primary: TRUE)
                ->add_key('username', unique: TRUE, name: 'username_unique')
                ->add_key('email', name: 'email_idx')
                ->add_key('role', name: 'role_idx')
                ->create_table('users');
            return;
        }

        $columns = $this->_lava->db->raw("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        $existing = array_fill_keys($columns, true);

        if (!isset($existing['password'])) {
            $this->_lava->db->raw("ALTER TABLE `users` ADD COLUMN `password` VARCHAR(255) NOT NULL");
        }

        if (!isset($existing['role'])) {
            $this->_lava->db->raw("ALTER TABLE `users` ADD COLUMN `role` ENUM('admin','moderator','user') NOT NULL DEFAULT 'user'");
        }

        if (!isset($existing['is_active'])) {
            $this->_lava->db->raw("ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1");
        }

        if (!isset($existing['created_at'])) {
            $this->_lava->db->raw("ALTER TABLE `users` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }

        if (!isset($existing['updated_at'])) {
            $this->_lava->db->raw("ALTER TABLE `users` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL");
        }
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('users');
    }
}