<?php

declare(strict_types=1);

use Pantono\Database\Migration\Base\BasePantonoMigration;

final class OneTimeLoginLinks extends BasePantonoMigration
{
    public function change(): void
    {
        $this->tablePrefix('login_one_time_link')
            ->addColumn('user_id', 'integer')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('date_logged_in', 'datetime', ['null' => true])
            ->addColumn('token', 'string')
            ->addColumn('deleted', 'boolean')
            ->create();
    }
}
