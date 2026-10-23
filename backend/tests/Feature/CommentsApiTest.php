<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Inn;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comentarios públicos: identidad por dispositivo, spam y moderación.
 */
class CommentsApiTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): Provider
    {
        return Provider::create([
            'business_name' => 'Seven Excursions',
            'type' => 'tour_operator',
            'status' => 'approved',
        ]);
    }

    private function tour(string $status = 'approved'): Tour
    {
        return Tour::create([
            'provider_id' => $this->provider()->id,
            'name' => 'Vuelta a la Isla en Chiva',
            'slug' => 'vuelta-a-la-isla-en-chiva',
            'status' => $status,
        ]);
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::create([
            'provider_id' => $this->provider()->id,
            'name' => 'La Regatta',
            'slug' => 'la-regatta',
            'status' => 'approved',
        ]);
    }

    private function inn(): Inn
    {
        return Inn::create([
            'provider_id' => $this->provider()->id,
            'name' => 'Casa Raizal',
            'slug' => 'casa-raizal',
            'status' => 'approved',
        ]);
    }

    private function device(string $id = 'DEVICE-ALPHA'): array
    {
        return ['X-Install-Id' => $id];
    }

    private function admin(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'author_name' => 'Marta',
            'body' => 'Salida temprano y vuelta con tiempo para el snorkel.',
        ], $overrides);
    }

    public function test_publica_un_comentario_y_lo_devuelve_publicado(): void
    {
        $tour = $this->tour();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.author_name', 'Marta')
            ->assertJsonPath('data.pending', false);

        $this->getJson("/api/v1/tours/{$tour->slug}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Salida temprano y vuelta con tiempo para el snorkel.');
    }

    public function test_no_expone_el_install_id_al_publico(): void
    {
        $tour = $this->tour();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $this->assertStringNotContainsString(
            'install_id',
            (string) $this->getJson("/api/v1/tours/{$tour->slug}/comments")->getContent(),
        );
    }

    public function test_requiere_el_identificador_del_dispositivo(): void
    {
        $tour = $this->tour();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_un_dispositivo_solo_opina_una_vez_por_destino(): void
    {
        $tour = $this->tour();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $this->payload(['body' => 'Otro intento desde el mismo movil.']),
            $this->device(),
        )->assertStatus(422)->assertJsonValidationErrors('body');

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_el_mismo_dispositivo_puede_opinar_en_otro_destino(): void
    {
        $tour = $this->tour();
        $restaurant = $this->restaurant();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();
        $this->postJson("/api/v1/restaurants/{$restaurant->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $this->assertDatabaseCount('comments', 2);
    }

    public function test_un_enlace_manda_el_comentario_a_revision(): void
    {
        $tour = $this->tour();

        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $this->payload(['body' => 'Mire mi pagina https://ofertas-promo.xyz para descuentos hoy mismo.']),
            $this->device(),
        )->assertCreated()->assertJsonPath('data.pending', true);

        $this->assertSame(Comment::PENDING, Comment::sole()->status);

        // Hasta que alguien lo apruebe no aparece en el listado público.
        $this->getJson("/api/v1/tours/{$tour->slug}/comments")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_una_opinion_legitima_se_publica_sin_revision(): void
    {
        $tour = $this->tour();

        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $this->payload(['body' => 'El guia fue excelente y el almuerzo incluido. Volveria sin duda.']),
            $this->device(),
        )->assertCreated()->assertJsonPath('data.pending', false);

        $this->assertSame(Comment::PUBLISHED, Comment::sole()->status);
    }

    public function test_el_honeypot_descarta_sin_guardar_nada(): void
    {
        $tour = $this->tour();

        // Responde 201 para que el bot no aprenda que el campo lo filtra,
        // pero no deja fila: el comentario sigue libre para el viajero real.
        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $this->payload(['website' => 'http://spam.example']),
            $this->device(),
        )->assertCreated();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_valida_los_campos_del_comentario(): void
    {
        $tour = $this->tour();

        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            ['author_name' => 'M', 'body' => 'corto', 'rating' => 9],
            $this->device(),
        )->assertStatus(422)->assertJsonValidationErrors(['author_name', 'body', 'rating']);
    }

    public function test_no_opina_sobre_un_tour_no_aprobado(): void
    {
        $tour = $this->tour('pending');

        $this->getJson("/api/v1/tours/{$tour->slug}/comments")->assertNotFound();
        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertNotFound();
    }

    public function test_los_comentarios_no_se_mezclan_entre_destinos(): void
    {
        $tour = $this->tour();
        $inn = $this->inn();

        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();
        $this->postJson("/api/v1/inns/{$inn->slug}/comments", $this->payload(), $this->device('DEVICE-BETA'))->assertCreated();

        $this->getJson("/api/v1/tours/{$tour->slug}/comments")->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/inns/{$inn->slug}/comments")->assertJsonCount(1, 'data');
    }

    public function test_el_panel_oculta_y_publica_comentarios(): void
    {
        $tour = $this->tour();
        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $comment = Comment::sole();

        $this->patchJson("/api/v1/admin/comments/{$comment->id}/status", ['status' => 'hidden'], $this->admin())
            ->assertOk();

        $this->getJson("/api/v1/tours/{$tour->slug}/comments")->assertJsonCount(0, 'data');

        $this->patchJson("/api/v1/admin/comments/{$comment->id}/status", ['status' => 'published'], $this->admin())
            ->assertOk();

        $this->getJson("/api/v1/tours/{$tour->slug}/comments")->assertJsonCount(1, 'data');
    }

    public function test_el_panel_filtra_por_estado(): void
    {
        $tour = $this->tour();
        $this->postJson(
            "/api/v1/tours/{$tour->slug}/comments",
            $this->payload(['body' => 'Pasen a este sitio https://spammy.link y ahorran mucho dinero hoy.']),
            $this->device(),
        )->assertCreated();

        $this->getJson('/api/v1/admin/comments?status=pending', $this->admin())
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/admin/comments?status=published', $this->admin())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_el_panel_borra_el_comentario(): void
    {
        $tour = $this->tour();
        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $comment = Comment::sole();

        $this->deleteJson("/api/v1/admin/comments/{$comment->id}", [], $this->admin())->assertOk();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_el_panel_requiere_el_token_de_administracion(): void
    {
        $tour = $this->tour();
        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $id = Comment::sole()->id;

        $this->getJson('/api/v1/admin/comments')->assertUnauthorized();
        $this->patchJson("/api/v1/admin/comments/{$id}/status", ['status' => 'hidden'])->assertUnauthorized();
        $this->deleteJson("/api/v1/admin/comments/{$id}")->assertUnauthorized();

        $this->assertSame(Comment::PUBLISHED, Comment::sole()->status);
    }

    public function test_el_panel_rechaza_un_estado_desconocido(): void
    {
        $tour = $this->tour();
        $this->postJson("/api/v1/tours/{$tour->slug}/comments", $this->payload(), $this->device())->assertCreated();

        $comment = Comment::sole();

        $this->patchJson(
            "/api/v1/admin/comments/{$comment->id}/status",
            ['status' => 'inventado'],
            $this->admin(),
        )->assertStatus(422)->assertJsonValidationErrors('status');
    }
}
