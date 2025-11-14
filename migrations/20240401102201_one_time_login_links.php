<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OneTimeLoginLinks extends AbstractMigration
{
    public function change(): void
    {
        $this->table('login_one_time_link')
            ->addColumn('user_id', 'integer')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('date_logged_in', 'datetime', ['null' => true])
            ->addColumn('token', 'string')
            ->create();
    }
}
