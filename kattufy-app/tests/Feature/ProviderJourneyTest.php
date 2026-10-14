<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_approved_provider_reaches_the_provider_dashboard(): void
    {
        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo')->assertRedirect(url('/provider/dashboard').'/');

        $this->get('/provider/dashboard/')->assertOk()
            ->assertSee('Northline Build Studio')
            ->assertSee('Approved')
            ->assertSee('Details match the sample documents. Welcome to Kattufy.')
            ->assertDontSee('ABCDE1234F')
            ->assertDontSee('Find services</a>', false);

        // Submitted details (masked ID) live on the verification status page.
        $this->get('/provider/verification/')->assertOk()
            ->assertSee('••••••234F')
            ->assertDontSee('ABCDE1234F');
    }

    public function test_pending_provider_sees_status_and_cannot_open_the_dashboard(): void
    {
        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo')->assertRedirect(url('/provider/verification').'/');

        $this->get('/provider/verification/')->assertOk()->assertSee('Pending review')->assertSee('Sample Pending Constructions');
        $this->get('/provider/dashboard/')->assertRedirect(url('/provider/verification').'/');
        $this->get('/provider/register/')->assertRedirect(url('/provider/verification').'/');
    }

    public function test_user_and_provider_areas_are_kept_apart(): void
    {
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $this->get('/provider/dashboard/')->assertRedirect(url('/user/dashboard').'/');
        $this->get('/provider/register/')->assertRedirect(url('/user/dashboard').'/');

        $this->post('/logout');

        $this->loginThroughOtp('9000000002', 'provider@kattufy.demo');
        $this->get('/user/dashboard/')->assertRedirect(url('/provider/dashboard').'/');
    }

    public function test_new_provider_submits_details_and_documents_for_review(): void
    {
        Storage::fake('local');
        $user = $this->newProviderAccount('9845011111');

        $response = $this->post('/provider/register', $this->form('9845011111') + [
            'action' => 'submit',
            'address_proof' => UploadedFile::fake()->create('rent-agreement.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect(url('/provider/verification').'/');

        $provider = $user->fresh()->provider;
        $this->assertSame(ProviderStatus::UnderReview, $provider->status);
        $this->assertSame('600042', $provider->pincode);
        $this->assertSame('velachery', $provider->locality->slug);
        $this->assertNotNull($provider->phone_verified_at);
        $this->assertSame(1, $provider->consents()->count());

        $proof = $provider->addressProof();
        Storage::disk('local')->assertExists($proof->document->original_path);
        $this->assertStringStartsWith('credentials/', $proof->document->original_path);
        $this->assertSame('private', $proof->document->visibility);

        // The ID number is encrypted at rest.
        $raw = DB::table('credentials')->where('id', $provider->idCredential()->id)->value('credential_number');
        $this->assertStringNotContainsString('PQRSX1234Z', $raw);
        $this->assertSame('PQRSX1234Z', $provider->idCredential()->credential_number);

        $this->get('/provider/verification/')->assertOk()->assertSee('Pending review')->assertSee('rent-agreement.pdf')->assertDontSee('PQRSX1234Z');
    }

    public function test_address_proof_is_required_to_submit_but_not_to_save_a_draft(): void
    {
        Storage::fake('local');
        $user = $this->newProviderAccount('9845022222');

        $this->post('/provider/register', $this->form('9845022222') + ['action' => 'submit'])->assertSessionHasErrors('address_proof');

        $this->post('/provider/register', $this->form('9845022222') + ['action' => 'save'])->assertRedirect(url('/provider/register').'/');
        $this->assertSame(ProviderStatus::Consented, $user->fresh()->provider->status);
    }

    public function test_aadhaar_numbers_and_bad_files_are_refused(): void
    {
        Storage::fake('local');
        $this->newProviderAccount('9845033333');

        $this->post('/provider/register', ['id_type' => 'voter-id', 'id_number' => '123412341234'] + $this->form('9845033333') + [
            'action' => 'submit',
            'address_proof' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors(['id_number', 'address_proof']);
    }

    public function test_a_different_business_number_gets_its_own_code_before_submission(): void
    {
        Storage::fake('local');
        $user = $this->newProviderAccount('9845044444');

        $this->post('/provider/register', $this->form('9876501234') + [
            'action' => 'submit',
            'address_proof' => UploadedFile::fake()->image('bill.jpg'),
        ])->assertRedirect(url('/login/verify').'/');

        $this->assertSame(ProviderStatus::Consented, $user->fresh()->provider->status);

        $this->post('/login/verify', ['code' => '123456'])->assertRedirect(url('/provider/verification').'/');
        $this->assertSame(ProviderStatus::UnderReview, $user->fresh()->provider->status);
    }

    public function test_documents_open_only_for_the_owner_through_a_signed_link(): void
    {
        $pending = Provider::where('slug', 'sample-pending-constructions')->first();
        $document = $pending->addressProof()->document;

        $this->get(route('documents.show', $document))->assertRedirect(); // unsigned: not allowed (guest)

        $this->loginThroughOtp('9000000003', 'pending@kattufy.demo');
        $this->get(route('documents.show', $document))->assertForbidden(); // no signature
        $this->get($document->temporaryUrl())->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->post('/logout');
        $this->loginThroughOtp('9000000001', 'user@kattufy.demo');
        $this->get($document->temporaryUrl())->assertForbidden();
    }

    private function newProviderAccount(string $phone): User
    {
        $user = User::create(['name' => 'Test Builder', 'phone' => '+91'.$phone, 'email' => $phone.'@example.com', 'status' => 'active']);
        $user->forceFill(['phone_verified_at' => now()])->save();
        $user->assignRole('provider');
        $this->actingAs($user);

        return $user;
    }

    private function form(string $businessPhone): array
    {
        return [
            'business_name' => 'Test Builders',
            'category_id' => DB::table('categories')->where('slug', 'interior-designers')->value('id'),
            'address' => '12 Test Street, Velachery, Chennai',
            'pincode' => '600042',
            'business_phone' => $businessPhone,
            'business_email' => 'office@testbuilders.example',
            'id_type' => 'pan-card',
            'id_number' => 'pqrsx1234z',
            'consent' => '1',
        ];
    }
}
