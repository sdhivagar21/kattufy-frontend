<?php

namespace Tests\Feature;

use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Services\LifecycleService;
use App\Support\ListingPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Listing plans, the provider overview and verification screens (front end), and the User dashboard. */
class ListingPlansUiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_each_plan_shows_only_its_own_fields(): void
    {
        $this->assertSame(['name', 'description'], ListingPlans::FIELDS['free']);
        $this->assertSame(['name', 'description', 'address', 'website', 'phone', 'whatsapp'], ListingPlans::FIELDS['pro']);

        foreach (['address', 'website', 'phone', 'whatsapp', 'email', 'photos', 'services', 'projects'] as $field) {
            $this->assertFalse(ListingPlans::shows('free', $field), "Free must not show {$field}");
        }
        foreach (['email', 'photos', 'services', 'projects'] as $field) {
            $this->assertFalse(ListingPlans::shows('pro', $field), "Pro must not show {$field}");
            $this->assertTrue(ListingPlans::shows('premium', $field), "Premium must show {$field}");
        }
        foreach (ListingPlans::FIELDS['pro'] as $field) {
            $this->assertTrue(ListingPlans::shows('premium', $field), "Premium must include everything in Pro ({$field})");
        }
        $this->assertSame(['free' => 300, 'pro' => 1000, 'premium' => 2000], ListingPlans::DESCRIPTION_LIMITS);
    }

    public function test_overview_keeps_statuses_apart_and_drops_the_duplicate_action_cards(): void
    {
        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo');

        $this->get('/provider/dashboard/')->assertOk()
            ->assertSee('Choose Your Company Listing')
            ->assertSee('Free Listing')->assertSee('Pro Listing')->assertSee('Premium Listing')
            ->assertSee('Everything in Pro, plus')
            ->assertSee('Customize plan')
            ->assertSee('Profile completion')
            ->assertSee('Public listing')
            ->assertSee('Choosing a plan does not verify, activate or publish your listing.')
            ->assertDontSee('₹')
            // The three duplicate quick-link cards are gone; the pages remain.
            ->assertDontSee('Documents, review notes and history.')
            ->assertDontSee('Your login details and language.');
    }

    public function test_plan_page_has_the_choose_details_review_flow(): void
    {
        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo');

        $this->get('/provider/plan/')->assertOk()
            ->assertSee('Choose Your Company Listing')
            ->assertSee('Select how your company appears on Kattufy. Each listing gives customers a different level of company information.')
            ->assertSee('Select plan')
            ->assertSee('Not active')
            ->assertSee('Payment not connected')
            ->assertSee('Prices are not set yet.')
            ->assertSee('Contact and location')
            ->assertSee('Services and projects')
            ->assertSee('Photos and videos')
            ->assertSee('Review and save')
            ->assertSee('Save changes')
            ->assertSee('Discard unsaved changes?')
            ->assertSee('Online payment is not connected. No payment is taken.')
            ->assertSee('Up to 300 characters on the Free Listing.', false)
            ->assertDontSee('₹');

        $this->get('/ta/provider/plan/')->assertOk()->assertSee('உங்கள் நிறுவனப் பட்டியலைத் தேர்ந்தெடுங்கள்');
    }

    public function test_profile_and_premium_pages(): void
    {
        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo');

        $this->get('/provider/profile/')->assertOk()
            ->assertSee('Description (English)')
            ->assertSee('Open listing plan')
            ->assertSee('name="business_email"', false);

        $this->get('/provider/services-and-projects/')->assertOk()->assertSee('Available with Premium Listing')->assertSee('Add service');
        $this->get('/provider/media/')->assertOk()->assertSee('MP4 or WebM')->assertSee('Files are not uploaded to Kattufy');
        $this->get('/provider/account/')->assertOk()->assertSee('Account settings')->assertSee('+91 90••• ••002');
    }

    public function test_plan_pages_need_an_approved_provider(): void
    {
        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo');
        $this->get('/provider/plan/')->assertRedirect(url('/provider/verification').'/');

        $this->post('/logout');
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $this->get('/provider/plan/')->assertRedirect(url('/user/dashboard').'/');
    }

    public function test_verification_page_shows_progress_and_checklist(): void
    {
        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo');

        $this->get('/provider/verification/')->assertOk()
            ->assertSee('Verification progress')
            ->assertSee('Admin review')
            ->assertSee('Verification checklist')
            ->assertSee('Address proof')
            // A civil contractor's contractor registration is required for the category.
            ->assertSee('Contractor registration')
            ->assertSee('Required for your category')
            ->assertSee('Submitted')
            ->assertDontSee('Update and resubmit');
    }

    public function test_changes_requested_shows_the_reason_and_a_resubmit_action(): void
    {
        $this->adminSignIn();
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();
        app(LifecycleService::class)->transition($pending, ProviderStatus::Consented, 'The address proof is unreadable. Please upload a clearer copy.');
        $this->post('/logout');

        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo');
        $this->get('/provider/verification/')->assertOk()
            ->assertSee('Action required')
            ->assertSee('The address proof is unreadable. Please upload a clearer copy.')
            ->assertSee('Update and resubmit');

        $this->get('/provider/register/')->assertOk()
            ->assertSee('Company information')
            ->assertSee('Identity proof')
            ->assertSee('Business registration documents')
            ->assertSee('Required')
            ->assertSee('Optional')
            ->assertSee('PDF, JPG, PNG or WebP · up to 8 MB');
    }

    public function test_public_profile_still_hides_email_and_address_without_a_stored_plan(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $provider = Provider::where('slug', 'northline-build-studio')->first();

        $this->get('/civil-contractors/northline-build-studio/')->assertOk()
            ->assertSee('Northline Build Studio')
            ->assertDontSee($provider->email)
            ->assertDontSee($provider->address)
            ->assertDontSee(substr($provider->phone, 3));
    }

    public function test_user_dashboard_shows_the_users_own_details_and_enquiries(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');

        $this->get('/user/dashboard/')->assertOk()
            ->assertSee('Welcome, Demo User')
            ->assertSee('My enquiries')
            ->assertSee('KTF-E-DEMO01')
            ->assertSee('Received')
            ->assertSee('View profile')
            ->assertDontSee('Northline Build Studio</h1>', false);
    }
}
