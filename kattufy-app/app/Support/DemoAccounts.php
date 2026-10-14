<?php

namespace App\Support;

/**
 * The fictional demo accounts (also created by DemoAccountsSeeder). Shown on
 * the login page only while demo mode is on. Picking one only fills in the
 * form: the one-time code still has to be typed.
 */
class DemoAccounts
{
    public const ACCOUNTS = [
        'user' => ['name' => 'Demo User', 'phone' => '9000000001', 'email' => 'user@kattufy.demo', 'role' => 'customer'],
        'provider' => ['name' => 'Demo Approved Provider', 'phone' => '9000000002', 'email' => 'provider@kattufy.demo', 'role' => 'provider'],
        'pending' => ['name' => 'Demo Pending Provider', 'phone' => '9000000003', 'email' => 'pending@kattufy.demo', 'role' => 'provider'],
        'admin' => ['name' => 'Demo Super Admin', 'phone' => '9000000004', 'email' => 'admin@kattufy.demo', 'role' => 'super_admin'],
    ];

    /** @return array<string, array{name: string, phone: string, email: string, role: string}> */
    public static function forLogin(): array
    {
        return self::ACCOUNTS;
    }

    public static function find(?string $key): ?array
    {
        return $key ? (self::ACCOUNTS[$key] ?? null) : null;
    }
}
