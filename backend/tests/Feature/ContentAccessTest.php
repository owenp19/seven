<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Propiedad de contenido editorial (promociones y noticias) en el panel
 * del proveedor (Parte 2).
 *
 * PromotionPolicy y NewsPolicy son las policies que faltaban: hasta ahora
 * promociones y noticias no tenían barrera de propiedad más allá del scoping
 * por provider_id. Aquí se verifica defensa en profundidad (Gate) y que un
 * recurso de otro proveedor responde 404 (IDOR).
 */
class ContentAccessTest extends TestCase
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

    private function tokenFor(User $user): string
    {
        return $user->createToken('content-test', ['provider'])->plainTextToken;
    }

    private function actAs(Provider $provider): void
    {
        $this->withToken($this->tokenFor($provider->user));
    }

    private function refreshAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
    }

    private function promotion(Provider $provider, string $title): Promotion
    {
        return Promotion::create([
            'provider_id' => $provider->id,
            'title' => $title,
            'status' => 'pending',
        ]);
    }

    private function news(Provider $provider, string $title, string $slug): News
    {
        return News::create([
            'title' => $title,
            'slug' => $slug,
            'provider_id' => $provider->id,
            'status' => 'draft',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Promociones
    |--------------------------------------------------------------------------
    */

    public function test_a_provider_manages_its_own_promotions(): void
    {
        $provider = $this->providerOwner('Promos A');
        $promotion = $this->promotion($provider, 'Promo propia');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->getJson("/api/v1/provider/promotions/{$promotion->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Promo propia');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->putJson("/api/v1/provider/promotions/{$promotion->id}", ['title' => 'Promo editada'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Promo editada');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->deleteJson("/api/v1/provider/promotions/{$promotion->id}")
            ->assertOk();

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_a_provider_cannot_touch_a_foreign_promotion(): void
    {
        $owner = $this->providerOwner('Dueño');
        $intruder = $this->providerOwner('Intruso');
        $foreign = $this->promotion($owner, 'Promo ajena');

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->getJson("/api/v1/provider/promotions/{$foreign->id}")
            ->assertNotFound();

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->putJson("/api/v1/provider/promotions/{$foreign->id}", ['title' => 'Robada'])
            ->assertNotFound();

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->deleteJson("/api/v1/provider/promotions/{$foreign->id}")
            ->assertNotFound();

        $this->assertSame('Promo ajena', $foreign->refresh()->title);
    }

    public function test_promotion_policy_grants_ownership_at_the_gate_level(): void
    {
        $owner = $this->providerOwner('Dueño');
        $intruder = $this->providerOwner('Intruso');
        $foreign = $this->promotion($owner, 'Promo ajena');
        $own = $this->promotion($intruder, 'Promo propia');

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($intruder->user)->allows($ability, $own));
            $this->assertTrue(Gate::forUser($intruder->user)->denies($ability, $foreign));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Noticias
    |--------------------------------------------------------------------------
    */

    public function test_a_provider_manages_its_own_news(): void
    {
        $provider = $this->providerOwner('Noticias A');
        $news = $this->news($provider, 'Nota propia', 'nota-propia');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->getJson("/api/v1/provider/news/{$news->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Nota propia');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->putJson("/api/v1/provider/news/{$news->id}", ['title' => 'Nota editada'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Nota editada');

        $this->refreshAuth();
        $this->actAs($provider);
        $this->deleteJson("/api/v1/provider/news/{$news->id}")
            ->assertOk();

        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }

    public function test_a_provider_cannot_touch_a_foreign_news_item(): void
    {
        $owner = $this->providerOwner('Dueño');
        $intruder = $this->providerOwner('Intruso');
        $foreign = $this->news($owner, 'Nota ajena', 'nota-ajena');

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->getJson("/api/v1/provider/news/{$foreign->id}")
            ->assertNotFound();

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->putJson("/api/v1/provider/news/{$foreign->id}", ['title' => 'Robada'])
            ->assertNotFound();

        $this->refreshAuth();
        $this->actAs($intruder);
        $this->deleteJson("/api/v1/provider/news/{$foreign->id}")
            ->assertNotFound();

        $this->assertSame('Nota ajena', $foreign->refresh()->title);
    }

    public function test_news_policy_grants_ownership_at_the_gate_level(): void
    {
        $owner = $this->providerOwner('Dueño');
        $intruder = $this->providerOwner('Intruso');
        $foreign = $this->news($owner, 'Nota ajena', 'nota-ajena');
        $own = $this->news($intruder, 'Nota propia', 'nota-propia');

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($intruder->user)->allows($ability, $own));
            $this->assertTrue(Gate::forUser($intruder->user)->denies($ability, $foreign));
        }
    }
}
