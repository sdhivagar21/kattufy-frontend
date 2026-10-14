<?php

namespace Tests\Feature;

use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Exceptions\TransitionNotAllowed;
use App\Modules\Verification\Services\CredentialReviewService;
use App\Modules\Verification\Services\LifecycleService;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_needs_both_the_mobile_and_the_email_code(): void
    {
        $admin = $this->demoUser('admin');

        $this->post('/admin/login', ['phone' => Phone::national($admin->phone), 'email' => $admin->email])->assertRedirect(url('/admin/verify-mobile').'/');
        $this->get('/admin')->assertRedirect(route('admin.login'));

        // The email step cannot be reached before the mobile code.
        $this->get('/admin/verify-email/')->assertRedirect(url('/admin/login').'/');

        $this->post('/admin/verify-mobile', ['code' => '123456'])->assertRedirect(url('/admin/verify-email').'/');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('admin.login'));

        $this->post('/admin/verify-email', ['code' => '123456'])->assertSessionHasErrors('code'); // mobile code is not the email code
        $this->post('/admin/verify-email', ['code' => '654321'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Overview')->assertSee('Pending review');
    }

    public function test_wrong_admin_details_are_refused_without_sending_a_code(): void
    {
        $this->post('/admin/login', ['phone' => '9000000004', 'email' => 'someone@kattufy.demo'])->assertSessionHasErrors('phone');
        $this->post('/admin/login', ['phone' => '9000000001', 'email' => 'user@kattufy.demo'])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_users_and_providers_cannot_open_the_admin_panel(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/providers')->assertRedirect(route('admin.login'));

        $this->post('/logout');
        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo');
        $this->get('/admin/providers')->assertRedirect(route('admin.login'));
    }

    public function test_admin_pages_render(): void
    {
        $this->adminSignIn();
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();

        $this->get('/admin/providers')->assertOk()->assertSee('Sample Pending Constructions');
        $this->get('/admin/providers/'.$pending->id)->assertOk()
            ->assertSee('Publish checklist')
            ->assertSee('Address proof')
            ->assertSee('sample-rental-agreement.pdf')
            ->assertSee('••••••7781')
            ->assertDontSee('KTF0007781');
        $this->get('/admin/providers/'.$pending->id.'/edit')->assertOk();
        $this->get('/admin/enquiries')->assertOk()->assertSee('KTF-E-DEMO01');
        $this->get('/admin/categories')->assertOk()->assertSee('Architects');
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/content-pages')->assertOk();
        $this->get('/admin/audit-logs')->assertOk();
    }

    public function test_approval_needs_the_checklist_and_shows_up_for_the_provider(): void
    {
        $admin = $this->adminSignIn();
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();
        $lifecycle = app(LifecycleService::class);

        // Documents not checked yet: publishing is refused.
        try {
            $lifecycle->transition($pending, ProviderStatus::Published);
            $this->fail('Publishing without checked documents must be refused.');
        } catch (TransitionNotAllowed $e) {
            $this->assertContains('Address proof checked', $e->failures);
        }

        foreach ($pending->credentials()->get() as $credential) {
            if ($credential->type->requires_document) {
                app(CredentialReviewService::class)->verify($credential, 'document_review');
            }
        }

        $lifecycle->transition($pending->fresh(), ProviderStatus::Published, 'All good.');
        $this->assertSame(ProviderStatus::Published, $pending->fresh()->status);
        $this->assertSame($admin->id, $pending->fresh()->reviewed_by);

        $this->post('/logout');
        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo')->assertRedirect(url('/provider/dashboard').'/');
        $this->get('/provider/dashboard/')->assertOk()->assertSee('Approved')->assertSee('All good.');
    }

    public function test_requesting_an_update_returns_the_submission_with_the_reason(): void
    {
        $this->adminSignIn();
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();

        try {
            app(LifecycleService::class)->transition($pending, ProviderStatus::Consented);
            $this->fail('A reason is required.');
        } catch (TransitionNotAllowed) {
        }

        app(LifecycleService::class)->transition($pending, ProviderStatus::Consented, 'The address proof is unreadable. Please upload a clearer copy.');

        $this->post('/logout');
        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo')->assertRedirect(url('/provider/verification').'/');
        $this->get('/provider/register/')->assertOk()
            ->assertSee('Needs update')
            ->assertSee('The address proof is unreadable. Please upload a clearer copy.');
        $this->get('/provider/verification/')->assertOk()->assertSee('Please update your details');
    }

    public function test_the_lifecycle_refuses_transitions_outside_the_table(): void
    {
        Auth::login($this->demoUser('admin'));
        $approved = Provider::where('slug', 'northline-build-studio')->first();

        $this->expectException(TransitionNotAllowed::class);
        app(LifecycleService::class)->transition($approved, ProviderStatus::Consented, 'not allowed');
    }

    public function test_every_status_change_is_recorded(): void
    {
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();
        $statuses = $pending->events()->whereNotNull('to_status')->pluck('to_status')->all();

        $this->assertSame(['under_review', 'consented'], $statuses);
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $pending->id, 'subject_type' => 'provider', 'event' => 'status_changed']);
    }
}
