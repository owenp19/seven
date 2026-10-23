<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moderación de contenido impuesta por el backend (Parte 3).
 *
 * El proveedor jamás publica por su cuenta: las promociones entran como
 * `pending` y las noticias como `draft` incluso si el payload manda
 * `active`/`published` (el campo status es admin-only y el default de la
 * base lo fija el banckoffice posteriormente). La app pública no muestra
 * material no aprobado.
 */
class ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function providerOwner(string $businessName = 'Moderación A'): Provider
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

    private function actAsProvider(Provider $provider): void
    {
        $this->withToken($provider->user->createToken('moderation', ['provider'])->plainTextToken);
    }

    private function adminToken(): string
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        return $admin->createToken('moderation-admin', ['admin'])->plainTextToken;
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    /*
    |--------------------------------------------------------------------------
    | Promociones: las crea el proveedor en pending
    |--------------------------------------------------------------------------
    */

    public function test_the_promotions_column_defaults_to_pending(): void
    {
        $promotion = Promotion::create([
            'provider_id' => 1,
            'title' => 'Sin estado',
        ])->refresh();

        $this->assertSame(Promotion::STATUS_PENDING, $promotion->status);
    }

    public function test_a_provider_created_promotion_is_pending_even_if_active_requested(): void
    {
        $provider = $this->providerOwner();

        $this->refreshAuth();
        $this->actAsProvider($provider);
        $response = $this->postJson('/api/v1/provider/promotions', [
            'title' => 'Promo del proveedor',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Promotion::STATUS_PENDING);

        $this->assertDatabaseHas('promotions', [
            'provider_id' => $provider->id,
            'title' => 'Promo del proveedor',
            'status' => Promotion::STATUS_PENDING,
        ]);

        // La app pública no la lista: solo salen las activas.
        $this->getJson('/api/v1/promotions')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Promo del proveedor']);
    }

    public function test_the_backoffice_can_publish_a_promotion(): void
    {
        $promotion = Promotion::create([
            'provider_id' => 1,
            'title' => 'Promo a publicar',
            'status' => Promotion::STATUS_PENDING,
        ]);

        $this->refreshAuth();
        $this->withToken($this->adminToken())
            ->patchJson("/api/v1/admin/promotions/{$promotion->id}", [
                'status' => Promotion::STATUS_ACTIVE,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', Promotion::STATUS_ACTIVE);

        $this->getJson('/api/v1/promotions')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Promo a publicar');
    }

    /*
    |--------------------------------------------------------------------------
    | Noticias: las crea el proveedor en draft
    |--------------------------------------------------------------------------
    */

    public function test_the_news_column_defaults_to_draft(): void
    {
        $news = News::create([
            'title' => 'Sin estado',
            'slug' => 'sin-estado',
            'provider_id' => 1,
        ])->refresh();

        $this->assertSame(News::STATUS_DRAFT, $news->status);
    }

    public function test_a_provider_created_news_item_is_draft_even_if_published_requested(): void
    {
        $provider = $this->providerOwner();

        $this->refreshAuth();
        $this->actAsProvider($provider);
        $response = $this->postJson('/api/v1/provider/news', [
            'title' => 'Nota del proveedor',
            'status' => 'published',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', News::STATUS_DRAFT);

        $this->assertDatabaseHas('news', [
            'provider_id' => $provider->id,
            'title' => 'Nota del proveedor',
            'status' => News::STATUS_DRAFT,
        ]);

        // La app pública no la lista ni la sirve por slug (borrador = 404).
        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Nota del proveedor']);

        $slug = $response->json('data.slug');
        $this->getJson("/api/v1/news/{$slug}")->assertNotFound();
    }

    public function test_the_backoffice_can_publish_a_news_item(): void
    {
        $news = News::create([
            'title' => 'Nota a publicar',
            'slug' => 'nota-a-publicar',
            'provider_id' => 1,
            'status' => News::STATUS_DRAFT,
        ]);

        $this->refreshAuth();
        $this->withToken($this->adminToken())
            ->patchJson("/api/v1/admin/news/{$news->id}", [
                'status' => News::STATUS_PUBLISHED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', News::STATUS_PUBLISHED);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Nota a publicar');
    }

    public function test_a_provider_cannot_reopen_a_rejected_status_via_panel(): void
    {
        $provider = $this->providerOwner();

        $promotion = Promotion::create([
            'provider_id' => $provider->id,
            'title' => 'Promo inactiva',
            'status' => Promotion::STATUS_INACTIVE,
        ]);

        $this->refreshAuth();
        $this->actAsProvider($provider);
        $this->patchJson("/api/v1/provider/promotions/{$promotion->id}", [
            'title' => 'Promo inactiva',
            'status' => Promotion::STATUS_ACTIVE,
        ])->assertOk();

        $this->assertSame(Promotion::STATUS_INACTIVE, $promotion->refresh()->status);
    }
}
