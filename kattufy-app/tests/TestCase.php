<?php

namespace Tests;

use App\Models\User;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Support\Phone;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against any database but the dedicated test database,
     * because RefreshDatabase drops every table it finds.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $database = $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');

        if ($database !== 'kattufy_testing') {
            throw new RuntimeException("Tests must use the kattufy_testing database, not [{$database}].");
        }

        return $app;
    }

    protected function demoUser(string $key): User
    {
        return User::where('email', $key.'@kattufy.demo')->firstOrFail();
    }

    /** Signs in through the real login + OTP flow (demo code). */
    protected function loginThroughOtp(string $phone, string $email, string $locale = 'en'): \Illuminate\Testing\TestResponse
    {
        $prefix = $locale === 'ta' ? '/ta' : '';

        $this->post($prefix.'/login', ['phone' => $phone, 'email' => $email])->assertRedirect(url($prefix.'/login/verify').'/');

        return $this->post($prefix.'/login/verify', ['code' => config('kattufy.demo_codes.mobile')]);
    }

    /** Completes the Super Admin sign-in: details, mobile code, email code. */
    protected function adminSignIn(): User
    {
        $admin = $this->demoUser('admin');

        $this->post('/admin/login', ['phone' => Phone::national($admin->phone), 'email' => $admin->email])->assertRedirect(url('/admin/verify-mobile').'/');
        $this->post('/admin/verify-mobile', ['code' => config('kattufy.demo_codes.mobile')])->assertRedirect(url('/admin/verify-email').'/');
        $this->post('/admin/verify-email', ['code' => config('kattufy.demo_codes.email')])->assertRedirect('/admin');

        return $admin;
    }

    protected function purpose(string $value): OtpPurpose
    {
        return OtpPurpose::from($value);
    }
}
