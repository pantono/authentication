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
            ->addIndex(['email_address'], ['unique' => true])
            ->create();

        $this->table('user_permission', ['id' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('permission_id', 'integer', ['signed' => false])
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('permission_id', 'permission', 'id')
            ->create();

        $this->table('user_group', ['id' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('group_id', 'integer', ['signed' => false])
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
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('api_token_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('token', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('date_last_used', 'datetime')
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('api_token_id', 'api_token', 'id')
            ->create();

        $this->table('user_password_reset')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('token', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('completed', 'boolean')
            ->addForeignKey('user_id', 'user', 'id')
            ->create();

        $this->table('social_login_provider')
            ->addColumn('name', 'string')
            ->addColumn('display_name', 'string')
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        $this->table('user_social_login')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('provider_id', 'integer', ['signed' => false])
            ->addColumn('provider_user_id', 'string') // The unique ID from the provider
            ->addColumn('access_token', 'text', ['null' => true])
            ->addColumn('refresh_token', 'text', ['null' => true])
            ->addColumn('token_expires', 'datetime', ['null' => true])
            ->addColumn('date_connected', 'datetime')
            ->addColumn('last_used', 'datetime', ['null' => true])
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('provider_id', 'social_login_provider', 'id')
            ->addIndex(['provider_id', 'provider_user_id'], ['unique' => true])
            ->create();
    }
}
