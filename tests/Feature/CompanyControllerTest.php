<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_company_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.company.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Company');
    }

    public function test_user_can_create_company(): void
    {
        $user = User::factory()->create();

        $companyData = [
            'name' => 'Test Company Inc.',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'info@testcompany.com',
            'website' => 'https://testcompany.com',
        ];

        $response = $this->actingAs($user)->post(route('profile.company.store'), $companyData);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'company-created');

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'name' => 'Test Company Inc.',
            'address' => '123 Main St',
            'phone' => '555-1234',
            'email' => 'info@testcompany.com',
            'website' => 'https://testcompany.com',
        ]);
    }

    public function test_first_company_becomes_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'First Company',
        ]);

        $company = Company::where('user_id', $user->id)->first();
        $this->assertTrue($company->is_default);
    }

    public function test_user_can_set_default_company(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->patch(route('profile.company.set-default', $company2));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Default company updated.');

        $this->assertTrue($company2->fresh()->is_default);
        $this->assertFalse($company1->fresh()->is_default);
    }

    public function test_user_cannot_view_other_users_companies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('profile.company.edit', $otherCompany));

        $response->assertStatus(403);
    }

    public function test_user_can_view_edit_form_for_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.company.edit', $company));

        $response->assertStatus(200);
        $response->assertSee('Edit Company');
        $response->assertSee($company->name);
    }

    public function test_user_can_update_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('profile.company.update', $company), [
            'name' => 'Updated Company Name',
            'address' => 'Updated Address',
            'phone' => '555-9999',
            'email' => 'updated@company.com',
            'website' => 'https://updated.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'company-updated');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company Name',
            'address' => 'Updated Address',
            'phone' => '555-9999',
            'email' => 'updated@company.com',
            'website' => 'https://updated.com',
        ]);
    }

    public function test_user_cannot_update_other_users_company(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCompany = Company::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('profile.company.update', $otherCompany), [
            'name' => 'Hacked Company',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_non_default_company(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company2));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Company deleted successfully.');
        $this->assertDatabaseMissing('companies', ['id' => $company2->id]);
    }

    public function test_cannot_delete_only_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Cannot delete your only company.');
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_deleting_default_company_sets_new_default(): void
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $company2 = Company::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $response = $this->actingAs($user)->delete(route('profile.company.destroy', $company1));

        $response->assertRedirect();
        $this->assertTrue($company2->fresh()->is_default);
    }

    public function test_company_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_company_email_must_be_valid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'Test Company',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_company_website_must_be_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.company.store'), [
            'name' => 'Test Company',
            'website' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('website');
    }
}
