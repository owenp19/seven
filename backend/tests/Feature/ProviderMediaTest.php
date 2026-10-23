<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Media;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media del proveedor (Parte 7).
 *
 * Cada archivo queda anclado al provider_id de su dueño: el proveedor solo
 * lista, edita y borra sus propios archivos (ajeno = 404), no puede anclar
 * media a servicios de otros (422 en el alta) y el backoffice que sube para
 * una vertical hereda el provider desde el morph target.
 */
class ProviderMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function providerOwner(string $businessName): Provider
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PROVEEDOR_TURISTICO);

        return Provider::create([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'type' => 'tour_operator',
            'status' => 'approved',
            'verification_status' => 'verified',
        ]);
    }

    private function actAs(Provider $provider): void
    {
        $this->app->make('auth')->forgetGuards();
        $this->withToken($provider->user->createToken('media-test', ['provider'])->plainTextToken);
    }

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function uploadFile(): UploadedFile
    {
        return UploadedFile::fake()->image('cover.png', 10, 10);
    }

    public function test_a_provider_uploads_media_owned_by_them(): void
    {
        Storage::fake('public');

        $owner = $this->providerOwner('Multimedia A');
        $business = Business::factory()->create(['provider_id' => $owner->id]);
        $this->actAs($owner);

        $this->post('/api/v1/provider/media', [
            'file' => $this->uploadFile(),
            'model_type' => Business::class,
            'model_id' => $business->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.model_type', Business::class)
            ->assertJsonPath('data.model_id', $business->id);

        $this->assertDatabaseHas('media', [
            'provider_id' => $owner->id,
            'model_type' => Business::class,
            'model_id' => $business->id,
        ]);
    }

    public function test_a_provider_cannot_anchor_media_to_foreign_services(): void
    {
        Storage::fake('public');

        $owner = $this->providerOwner('Multimedia A');
        $foreignBusiness = Business::factory()->create(['provider_id' => $this->providerOwner('Otro')->id]);
        $this->actAs($owner);

        $this->post('/api/v1/provider/media', [
            'file' => $this->uploadFile(),
            'model_type' => Business::class,
            'model_id' => $foreignBusiness->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('model_id');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_a_provider_only_sees_and_manages_their_own_media(): void
    {
        Storage::fake('public');

        $ownerA = $this->providerOwner('Multimedia A');
        $ownerB = $this->providerOwner('Multimedia B');

        $this->actAs($ownerA);
        $ownedByA = $this->post('/api/v1/provider/media', ['file' => $this->uploadFile()])
            ->assertCreated()
            ->json('data.id');

        $this->actAs($ownerB);
        $ownedByB = $this->post('/api/v1/provider/media', ['file' => $this->uploadFile()])
            ->assertCreated()
            ->json('data.id');

        // B no ve el archivo de A.
        $this->actAs($ownerB);
        $this->getJson('/api/v1/provider/media')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedByB);

        $this->getJson('/api/v1/provider/media/'.$ownedByA)->assertNotFound();
        $this->patchJson("/api/v1/provider/media/{$ownedByA}", ['type' => 'gallery'])->assertNotFound();
        $this->deleteJson("/api/v1/provider/media/{$ownedByA}")->assertNotFound();

        $this->assertDatabaseHas('media', ['id' => $ownedByA, 'provider_id' => $ownerA->id]);

        // El dueño sí puede reasignar y borrar.
        $this->actAs($ownerA);
        $this->patchJson("/api/v1/provider/media/{$ownedByA}", ['type' => 'gallery'])
            ->assertOk()
            ->assertJsonPath('data.type', 'gallery');

        $this->deleteJson("/api/v1/provider/media/{$ownedByA}")->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $ownedByA]);
    }

    public function test_the_backoffice_inherits_the_owner_when_uploading_for_a_service(): void
    {
        Storage::fake('public');

        $owner = $this->providerOwner('Multimedia C');
        $business = Business::factory()->create(['provider_id' => $owner->id]);

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', [
                'file' => $this->uploadFile(),
                'model_type' => Business::class,
                'model_id' => $business->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('media', [
            'provider_id' => $owner->id,
            'model_type' => Business::class,
            'model_id' => $business->id,
        ]);
    }
}
