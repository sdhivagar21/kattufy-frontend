<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounts\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAndLoginTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private array $kavya = ['name' => 'Kavya Shankar', 'phone' => '98400 12345', 'email' => 'kavya@example.com'];

    public function test_registration_is_saved_before_the_code_is_verified(): void
    {
        $this->post('/register', $this->kavya)->assertRedirect(url('/login/verify').'/');

        $user = User::where('phone', '+919840012345')->first();
        $this->assertNotNull($user, 'The registration must be saved before OTP verification.');
        $this->assertNull($user->phone_verified_at);
        $this->assertSame('kavya@example.com', $user->email);
        $this->assertGuest();
        $this->assertSame(1, OtpCode::where('phone', '+919840012345')->where('purpose', 'register')->count());
        $this->assertNotSame('123456', OtpCode::first()->code_hash, 'Codes are stored hashed.');
    }

    public function test_repeating_the_registration_does_not_create_a_second_account(): void
    {
        $this->post('/register', $this->kavya);
        $this->travel(61)->seconds();
        $this->post('/register', $this->kavya);

        $this->assertSame(1, User::where('phone', '+919840012345')->count());
    }

    public function test_registered_numbers_and_used_emails_are_refused(): void
    {
        $this->post('/register', ['name' => 'Someone', 'phone' => '9000000001', 'email' => 'new@example.com'])
            ->assertSessionHasErrors('phone');

        $this->post('/register', ['name' => 'Someone', 'phone' => '9876543210', 'email' => 'user@kattufy.demo'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, User::where('phone', '+919876543210')->count());
    }

    public function test_full_registration_verifies_mobile_then_asks_for_a_role(): void
    {
        $this->post('/register', $this->kavya);

        $this->post('/login/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/verify', ['code' => '123456'])->assertRedirect(url('/select-role').'/');

        $user = User::where('phone', '+919840012345')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->phone_verified_at);

        $this->get('/select-role/')->assertOk()->assertSee('How would you like to continue?')->assertSee('Continue as User')->assertSee('Continue as Provider');
    }

    public function test_role_choice_accepts_only_user_or_provider_and_happens_once(): void
    {
        $this->post('/register', $this->kavya);
        $this->post('/login/verify', ['code' => '123456']);

        $this->post('/select-role', ['role' => 'super_admin'])->assertSessionHasErrors('role');
        $this->assertFalse(User::where('phone', '+919840012345')->first()->hasRole('super_admin'));

        $this->post('/select-role', ['role' => 'customer'])->assertRedirect(url('/user/dashboard').'/');
        $this->get('/select-role/')->assertRedirect(url('/user/dashboard').'/');
        $this->post('/select-role', ['role' => 'provider'])->assertRedirect(url('/user/dashboard').'/');

        $this->assertFalse(User::where('phone', '+919840012345')->first()->hasRole('provider'));
    }

    public function test_login_never_creates_an_account_and_hides_which_part_was_wrong(): void
    {
        $before = User::count();

        $this->post('/login', ['phone' => '9123456789', 'email' => 'nobody@example.com'])->assertSessionHasErrors('phone');
        $this->post('/login', ['phone' => '9000000001', 'email' => 'wrong@example.com'])->assertSessionHasErrors('phone');

        $this->assertSame($before, User::count());
        $this->assertSame(0, OtpCode::count());
    }

    public function test_submitting_the_login_form_does_not_sign_in(): void
    {
        $this->post('/login', ['phone' => '9000000001', 'email' => 'user@kattufy.demo'])->assertRedirect(url('/login/verify').'/');
        $this->assertGuest();
        $this->get('/user/dashboard/')->assertRedirect(url('/login').'/');
    }

    public function test_existing_user_logs_in_with_mobile_email_and_code(): void
    {
        $this->loginThroughOtp('9000000001', 'USER@kattufy.demo')->assertRedirect(url('/user/dashboard').'/');

        $this->assertAuthenticatedAs($this->demoUser('user'));
        $this->assertNotNull($this->demoUser('user')->last_login_at);

        $this->get('/user/dashboard/')->assertOk()
            ->assertSee('Welcome, Demo User')
            ->assertSee('Questions and answers')
            ->assertSee('What is Kattufy?')
            ->assertSee('+91 90••• ••001')
            ->assertDontSee('9000000001');
    }

    public function test_a_code_dies_after_five_wrong_attempts(): void
    {
        $this->post('/login', ['phone' => '9000000001', 'email' => 'user@kattufy.demo']);

        foreach (range(1, 5) as $i) {
            $this->post('/login/verify', ['code' => '111111']);
        }

        $this->post('/login/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->post('/login', ['phone' => '9000000001', 'email' => 'user@kattufy.demo']);
        $this->travel(6)->minutes();

        $this->post('/login/verify', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_codes_cannot_be_requested_again_within_sixty_seconds(): void
    {
        $this->post('/login', ['phone' => '9000000001', 'email' => 'user@kattufy.demo']);
        $this->post('/login/resend')->assertSessionHasErrors('code');
        $this->assertSame(1, OtpCode::count());
    }

    public function test_staff_use_the_same_login_and_still_need_both_codes(): void
    {
        $this->get('/login/')->assertOk()->assertDontSee('Super Admin login');

        $this->post('/login', ['phone' => '9000000004', 'email' => 'admin@kattufy.demo'])->assertRedirect(url('/admin/verify-mobile').'/');

        // The member code step cannot finish a staff sign-in.
        $this->post('/login/verify', ['code' => config('kattufy.demo_codes.mobile')]);
        $this->assertGuest();

        $this->post('/admin/verify-mobile', ['code' => config('kattufy.demo_codes.mobile')])->assertRedirect(url('/admin/verify-email').'/');
        $this->assertGuest();

        $this->post('/admin/verify-email', ['code' => config('kattufy.demo_codes.email')])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->demoUser('admin'));
    }

    public function test_tamil_login_lands_on_the_tamil_dashboard(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo', 'ta')->assertRedirect(url('/ta/user/dashboard').'/');
        $this->get('/ta/user/dashboard/')->assertOk()->assertSee('வருக, Demo User');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $this->post('/logout')->assertRedirect(url('/login').'/');
        $this->assertGuest();
    }
}
