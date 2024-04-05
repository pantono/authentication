<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Authentication extends AbstractMigration
{
    public function change(): void
    {
        $this->table('permission')
            ->addColumn('name', 'string')
            ->addColumn('description', 'string')
            ->create();

        $this->table('group')
            ->addColumn('name', 'string')
            ->addColumn('description', 'string')
            ->create();

        $this->table('user')
            ->addColumn('email_address', 'string')
            ->addColumn('forename', 'string')
            ->addColumn('surname', 'string')
            ->addColumn('password', 'string')
            ->addColumn('deleted', 'boolean')
            ->addColumn('disabled', 'boolean')
            ->create();

        $this->table('user_permission', ['id' => false])
            ->addColumn('user_id', 'integer')
            ->addColumn('permission_id', 'integer')
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('permission_id', 'permission', 'id')
            ->create();

        $this->table('user_group', ['id' => false])
            ->addColumn('user_id', 'integer')
            ->addColumn('group_id', 'integer')
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('group_id', 'group', 'id')
            ->create();

        $this->table('api_token')
            ->addColumn('application_name', 'string')
            ->addColumn('token', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('date_last_used', 'datetime')
            ->create();

        $this->table('user_token')
            ->addColumn('user_id', 'integer')
            ->addColumn('api_token_id', 'integer')
            ->addColumn('token', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('date_last_used', 'datetime')
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('api_token_id', 'api_token', 'id')
            ->create();

        $this->table('user_password_reset')
            ->addColumn('user_id', 'integer')
            ->addColumn('token', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('completed', 'boolean')
            ->addForeignKey('user_id', 'user', 'id')
            ->create();
    }
}
