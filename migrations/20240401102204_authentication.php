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
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_last_login', 'datetime', ['null' => true])
            ->addColumn('forename', 'string')
            ->addColumn('surname', 'string')
            ->addColumn('password', 'string')
            ->addColumn('deleted', 'boolean')
            ->addColumn('disabled', 'boolean')
            ->addIndex(['email_address'], ['unique' => true])
            ->create();

        $this->table('user_field_type')
            ->addColumn('name', 'string')
            ->addColumn('type', 'string')
            ->addColumn('required', 'boolean')
            ->create();

        $this->table('user_field')
            ->addColumn('user_id', 'integer')
            ->addColumn('field_type_id', 'integer')
            ->addColumn('value', 'text')
            ->addForeignKey('field_type_id', 'user_field_type', 'id')
            ->addForeignKey('user_id', 'user', 'id')
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

        $this->table('login_provider_type')
            ->addColumn('name', 'string')
            ->addColumn('provider_class', 'string')
            ->addColumn('allows_registration', 'boolean', ['default' => 0])
            ->addColumn('required_fields', 'json')
            ->addIndex(['name'], ['unique' => true])
            ->create();

        if ($this->isMigratingUp()) {
            $this->table('login_provider_type')
                ->insert([
                    ['name' => 'Username & Password', 'provider_class' => 'Pantono\Authentication\Provider\PasswordAuthentication', 'allows_registration' => 1, 'required_fields' => json_encode([])],
                    ['name' => 'Login with google', 'provider_class' => 'Pantono\Authentication\Provider\GoogleAuthProvider', 'allows_registration' => 1, 'required_fields' => json_encode([
                        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'text', 'required' => true],
                        ['name' => 'redirect_uri', 'label' => 'Redirect URI', 'type' => 'text', 'required' => true],
                    ])],
                    ['name' => 'Login with apple', 'provider_class' => 'Pantono\Authentication\Provider\AppleAuthProvider', 'allows_registration' => 1, 'required_fields' => json_encode([
                        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'text', 'required' => true],
                        ['name' => 'redirect_uri', 'label' => 'Redirect URI', 'type' => 'text', 'required' => true],
                    ])],
                ])->saveData();
        }

        $this->table('login_provider')
            ->addColumn('type_id', 'integer', ['signed' => false])
            ->addColumn('config', 'json')
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addForeignKey('type_id', 'social_login_provider_type', 'id')
            ->create();

        $this->table('login_provider_user')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('provider_id', 'integer', ['signed' => false])
            ->addColumn('provider_user_id', 'string') // The unique ID from the provider
            ->addColumn('access_token', 'text', ['null' => true])
            ->addColumn('refresh_token', 'text', ['null' => true])
            ->addColumn('token_expires', 'datetime', ['null' => true])
            ->addColumn('date_connected', 'datetime')
            ->addColumn('last_used', 'datetime', ['null' => true])
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('provider_id', 'login_provider', 'id')
            ->addIndex(['provider_id', 'provider_user_id'], ['unique' => true])
            ->create();

        $this->table('user_history')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('date', 'datetime')
            ->addColumn('entry', 'string')
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('authentication_log')
            ->addColumn('provider_id', 'integer')
            ->addColumn('date', 'datetime')
            ->addColumn('session_id', 'string')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ip_address', 'string', ['null' => true])
            ->addColumn('entry', 'text')
            ->addColumn('data', 'json', ['null' => true])
            ->addIndex('session_id')
            ->addForeignKey('provider_id', 'login_provider', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
