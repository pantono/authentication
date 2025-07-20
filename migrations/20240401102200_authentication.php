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
            ->addColumn('tfa_enabled', 'boolean')
            ->addColumn('system_user', 'boolean', ['default' => 0])
            ->addColumn('verified', 'boolean', ['default' => 0])
            ->addIndex(['email_address'], ['unique' => true])
            ->create();

        $this->table('user_field_type')
            ->addColumn('name', 'string')
            ->addColumn('type', 'string')
            ->addColumn('required', 'boolean')
            ->create();

        $this->table('user_field')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('field_type_id', 'integer', ['signed' => false])
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
            ->addForeignKey('type_id', 'login_provider_type', 'id')
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
            ->addColumn('values', 'json', ['null' => true])
            ->addForeignKey('user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('provider_id', 'login_provider', 'id')
            ->create();

        $this->table('user_history')
            ->addColumn('target_user_id', 'integer', ['signed' => false])
            ->addColumn('by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('date', 'datetime')
            ->addColumn('entry', 'string')
            ->addForeignKey('target_user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('by_user_id', 'user', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('authentication_log')
            ->addColumn('provider_id', 'integer', ['signed' => false])
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

        if ($this->isMigratingUp()) {
            $this->table('user')
                ->insert([
                    ['id' => 1, 'email_address' => 'unknown@user', 'forename' => 'Unknown', 'surname' => 'User', 'deleted' => 0, 'disabled' => 1, 'password' => '', 'system_user' => 1],
                    ['id' => 2, 'email_address' => 'system@user', 'forename' => 'System', 'surname' => 'User', 'deleted' => 0, 'disabled' => 1, 'password' => '', 'system_user' => 1],
                ])->saveData();
        }

        $this->table('tfa_type')
            ->addColumn('name', 'string')
            ->addColumn('description', 'string')
            ->addColumn('enabled', 'boolean', ['default' => 0])
            ->addColumn('controller', 'string')
            ->addColumn('config', 'json', ['null' => true])
            ->create();
        if ($this->isMigratingUp()) {
            $this->table('tfa_type')
                ->insert([
                    ['id' => 1, 'name' => 'Email', 'description' => 'An e-mail sent to your inbox', 'enabled' => 1, 'controller' => 'Pantono\Authentication\Provider\Tfa\EmailTfaProvider', 'config' => json_encode(['email_copy' => '<p>Your code is {code}</p>', 'verification_required' => false, 'custom_email' => false, 'email_verification_copy' => '<p>Click this link {link} to verify your e-mail</p>'])],
                    ['id' => 2, 'name' => 'SMS', 'description' => 'An SMS sent to your mobile phone', 'enabled' => 0, 'controller' => 'Pantono\Authentication\Provider\Tfa\SmsTfaProvider', 'config' => json_encode(['verification_required' => true, 'sid' => '', 'token' => '', 'from_number' => ''])],
                    ['id' => 3, 'name' => 'TOTP', 'description' => 'A time-based one-time password', 'enabled' => 0, 'controller' => 'Pantono\Authentication\Provider\Tfa\TotpTfaProvider', 'config' => json_encode(['qr_label' => 'Pantono'])],
                ])->save();
        }

        $this->table('user_tfa_method')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_last_used', 'datetime', ['null' => true])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('type_id', 'integer', ['signed' => false])
            ->addColumn('config', 'json')
            ->addColumn('enabled', 'boolean', ['default' => 1])
            ->addColumn('verified', 'boolean', ['default' => 0])
            ->addColumn('deleted', 'boolean')
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('type_id', 'tfa_type', 'id')
            ->create();

        $this->table('user_tfa_attempt')
            ->addColumn('method_id', 'integer', ['signed' => false])
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('attempt_code', 'string')
            ->addColumn('attempt_slug', 'string')
            ->addColumn('verified', 'boolean', ['default' => 0])
            ->addForeignKey('method_id', 'user_tfa_method', 'id')
            ->create();

        $this->table('user_tfa_attempt_log')
            ->addColumn('attempt_id', 'integer', ['signed' => false])
            ->addColumn('date', 'datetime')
            ->addColumn('entry', 'string')
            ->create();

        $this->table('user_verification_type')
            ->addColumn('name', 'string')
            ->addColumn('enabled', 'boolean')
            ->addColumn('controller', 'string')
            ->create();

        if ($this->isMigratingUp()) {
            $this->table('user_verification_type')
                ->insert([
                    ['id' => 1, 'name' => 'Email', 'enabled' => 1, 'controller' => 'Pantono\Authentication\VerificationController\EmailVerificationController'],
                    ['id' => 2, 'name' => 'SMS', 'enabled' => 0, 'controller' => 'Pantono\Authentication\VerificationController\SmsVerificationController'],
                    ['id' => 3, 'name' => 'WhatsApp', 'enabled' => 0, 'controller' => 'Pantono\Authentication\VerificationController\WhatsAppVerificationController'],
                ])->saveData();
        }

        $this->table('user_verification')
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('type_id', 'integer', ['signed' => false])
            ->addColumn('token', 'string')
            ->addColumn('code', 'string')
            ->addColumn('credential', 'string')
            ->addColumn('date_created', 'datetime')
            ->addColumn('date_expires', 'datetime')
            ->addColumn('verified', 'boolean', ['default' => 0])
            ->addForeignKey('user_id', 'user', 'id')
            ->addForeignKey('type_id', 'user_verification_type', 'id')
            ->addIndex('code')
            ->addIndex('token')
            ->create();

        $this->table('user_verification_log')
            ->addColumn('verification_id', 'integer', ['signed' => false])
            ->addColumn('date', 'datetime')
            ->addColumn('entry', 'string')
            ->addForeignKey('verification_id', 'user_verification', 'id')
            ->create();
    }
}
