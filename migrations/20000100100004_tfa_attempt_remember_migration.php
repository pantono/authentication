<?php

declare(strict_types=1);

use Pantono\Database\Migration\Base\BasePantonoMigration;

final class TfaAttemptRememberMigration extends BasePantonoMigration
{
    public function change(): void
    {
        $this->tablePrefix('user_tfa_attempt')
            ->addColumn('remember', 'boolean', ['default' => false])
            ->addColumn('remember_expires', 'datetime', ['null' => true])
            ->update();
    }
}
