<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * MigrationController
 *
 * Exposes the Migration library through routes (see app/config/routes.php)
 * and through the CLI:  php lava migration run
 *
 * The library refuses to run unless MIGRATION_ENABLED=true in your .env,
 * so these URLs are inert on a deployed server.
 */
class MigrationController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('migration');
    }

    public function create_migration($migration_class)
    {
        $this->migration->create_migration($migration_class);
    }

    public function migrate()
    {
        $this->migration->migrate();
    }

    public function rollback()
    {
        $this->migration->rollback();
    }

    public function rollback_all()
    {
        $this->migration->rollback_all();
    }

    public function refresh()
    {
        $this->migration->refresh();
    }

    public function status()
    {
        $this->migration->status();
    }
}
