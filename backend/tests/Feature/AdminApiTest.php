<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\News;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    public function test_admin_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertStatus(401);
        $this->postJson('/api/v1/admin/categories', ['name' => 'X'])->assertStatus(401);

        $this->withHeaders(['X-Admin-Token' => 'wrong-token'])
            ->getJson('/api/v1/admin/categories')
            ->assertStatus(401);
    }

    public function test_admin_crud_category_and_flushes_catalog_cache(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/categories', [
                'slug' => 'tours',
                'name' => 'Tours y experiencias',
                'icon' => 'boat-outline',
                'sort_order' => 3,
                'is_active' => true,
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'tours');

        // El catálogo público (cacheado por versión) refleja el alta de inmediato.
        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'tours']);

        $category = Category::where('slug', 'tours')->firstOrFail();

        $this->withHeaders($this->adminHeaders())
            ->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Tours editado'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Tours editado');

        $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_manages_banner_and_promotion(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/banners', [
                'title' => 'San Andrés te espera',
                'status' => 'active',
                'order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'San Andrés te espera');

        $banner = Banner::firstOrFail();

        $this->getJson('/api/v1/banners')
            ->assertOk()
            ->assertJsonFragment(['title' => 'San Andrés te espera']);

        $provider = Provider::create([
            'business_name' => 'Seven Excursions',
            'owner_name' => 'Carlos',
            'email' => 'excursions@seven.test',
            'phone' => '+57 300 000 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/promotions', [
                'provider_id' => $provider->id,
                'title' => '20% descuento cena romántica',
                'discount' => 20,
                'status' => 'active',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('promotions', 1);
        $this->assertDatabaseHas('promotions', ['provider_id' => $provider->id]);

        $this->withHeaders($this->adminHeaders())
            ->deleteJson('/api/v1/admin/banners/'.$banner->id)
            ->assertOk();

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_admin_manages_news_end_to_end(): void
    {
        $provider = Provider::create([
            'business_name' => 'Exploria Tours San Andrés',
            'owner_name' => 'Exploria Tours',
            'email' => 'exploria@seven.test',
            'phone' => '+57 8 512 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/news', [
                'title' => 'Vuelve el Festival de la Arepa de Huevo',
                'slug' => 'festival-arepa-huevo',
                'kind' => News::KIND_EVENT,
                'excerpt' => 'Tres días de música en vivo.',
                'provider_id' => $provider->id,
                'is_featured' => true,
                'status' => News::STATUS_PUBLISHED,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'festival-arepa-huevo')
            ->assertJsonPath('data.is_featured', true);

        $news = News::firstOrFail();

        // El resource de admin trae el operador; el público, también.
        $response->assertJsonPath('data.provider.business_name', 'Exploria Tours San Andrés');

        // Publicar una nota tiene que cambiarla en el feed sin esperar al TTL.
        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Vuelve el Festival de la Arepa de Huevo');

        // Retirarla la saca del feed sin borrar la fila.
        $this->withHeaders($this->adminHeaders())
            ->putJson('/api/v1/admin/news/'.$news->id, ['status' => News::STATUS_DRAFT])
            ->assertOk()
            ->assertJsonPath('data.status', News::STATUS_DRAFT);

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($this->adminHeaders())
            ->deleteJson('/api/v1/admin/news/'.$news->id)
            ->assertOk();

        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }

    /**
     * Editar una nota reenvía su propio slug, así que la regla de unicidad tiene
     * que excluirse a sí misma o el guardado fallaría contra el registro que se
     * está editando.
     */
    public function test_admin_can_resubmit_the_same_slug_when_editing_news(): void
    {
        $news = News::create([
            'title' => 'Festival de la Arepa de Huevo',
            'slug' => 'festival-arepa-huevo',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->putJson('/api/v1/admin/news/'.$news->id, [
                'title' => 'Festival de la Arepa de Huevo 2026',
                'slug' => 'festival-arepa-huevo',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Festival de la Arepa de Huevo 2026');
    }

    public function test_admin_rejects_a_duplicated_or_invalid_news_slug(): void
    {
        News::create([
            'title' => 'Festival de la Arepa de Huevo',
            'slug' => 'festival-arepa-huevo',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/news', [
                'title' => 'Otra nota con el mismo slug',
                'slug' => 'festival-arepa-huevo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');

        // El slug se genera con guiones, así que un espacio no puede aceptarse.
        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/news', [
                'title' => 'Nota con slug inválido',
                'slug' => 'nota con espacios',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_admin_news_requires_the_token(): void
    {
        $this->getJson('/api/v1/admin/news')->assertStatus(401);
        $this->postJson('/api/v1/admin/news', ['title' => 'Sin token'])->assertStatus(401);
    }

    public function test_admin_creates_tour_with_relations_and_filter(): void
    {
        $provider = Provider::create([
            'business_name' => 'Seven Excursions',
            'owner_name' => 'Carlos',
            'email' => 'excursions@seven.test',
            'phone' => '+57 300 000 0000',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/tours', [
                'provider_id' => $provider->id,
                'name' => 'Johnny Cay',
                'slug' => 'johnny-cay',
                'price' => 170000,
                'status' => 'approved',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Johnny Cay');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/tours?status=approved')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/tours?status=pending')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/tours/johnny-cay')->assertOk();
    }

    public function test_admin_validates_payload(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/categories', ['name' => 'Sin slug'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['slug']]);

        $this->withHeaders($this->adminHeaders())
            ->postJson('/api/v1/admin/promotions', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['title']]);
    }
}
