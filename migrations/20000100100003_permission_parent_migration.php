<?php

declare(strict_types=1);

use Pantono\Database\Migration\Base\BasePantonoMigration;

final class PermissionParentMigration extends BasePantonoMigration
{
    public function change(): void
    {
        $this->tablePrefix('permission')
            ->addLinkedColumn('parent_id', $this->addTablePrefix('permission'), 'id', ['null' => true])
            ->update();
    }
}
