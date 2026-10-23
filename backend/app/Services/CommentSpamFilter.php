<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * CommentSpamFilter
 *
 * Decide si un comentario necesita revisión manual antes de publicarse.
 *
 * El objetivo no es perfectionar: la mayoría de los cuerpos legítimos pasan
 * intactos y el filtro solo degrada a `pending` los casos con señales
 * inequívocas. Nunca rechaza, porque un filtro que bloquea opiniones reales
 * cuesta más confianza que el spam que deja pasar, y el panel puede quitar
 * lo que se cuele.
 *
 * Las señales son estructurales, no léxicas: enlaces, mayúsculas sostenidas,
 * relleno de caracteres y texto repetido. No se mira ninguna palabra concreta
 * porque "comida", "precio" o "taxi" son vocabulario legítimo en reseñas de
 * viaje, y una lista negra de términos acaba bloqueando las reseñas que más
 * útil le son al viajero.
 */
final class CommentSpamFilter
{
    /**
     * Cualquier enlace es material publicitario sistemático. Se cuenta como
     * revisión incluso con uno solo, porque un comentario de viaje con un
     * único enlace también suele ser un enlace de afiliado disfrazado.
     */
    private const LINK_PATTERNS = [
        '~https?://~i',
        '~www\.~i',
        '~\b(?:t\.me|wa\.me|telegram|whatsapp|bit\.ly|linktr\.ee)\b~i',
        '~\b[a-z0-9][a-z0-9-]*\.(?:com|net|org|co|io|xyz|top|ru|cn|shop|link|biz|info|app)\b~i',
    ];

    /**
     * Evalúa el cuerpo del comentario.
     *
     * @return array{0: bool, 1: list<string>} Tupla [necesita revisión, motivos]
     *                                         Los motivos se registran
     *                                         en el log: son el único
     *                                         indicio que queda cuando
     *                                         alguien pregunta por qué
     *                                         un comentario no salió
     *                                         publicado.
     */
    public function evaluate(string $body): array
    {
        $text = trim($body);
        $reasons = [];

        if ($this->hasLink($text)) {
            $reasons[] = 'enlace';
        }

        if ($this->hasShouting($text)) {
            $reasons[] = 'mayusculas';
        }

        if ($this->hasCharacterFiller($text)) {
            $reasons[] = 'caracteres-repetidos';
        }

        if ($this->hasUppercaseSpam($text)) {
            $reasons[] = 'texto-en-mayusculas';
        }

        return [$reasons !== [], $reasons];
    }

    private function hasLink(string $text): bool
    {
        foreach (self::LINK_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Más del 60% de las letras en mayúsculas ya no es "escribir rápido", es
     * gritar. El mínimo absoluto de caracteres evita marcar "OK" o "WOW" como
     * spam.
     */
    private function hasShouting(string $text): bool
    {
        $letters = preg_replace('/[^\pL]/u', '', $text) ?? '';

        if (Str::length($letters) < 20) {
            return false;
        }

        $upper = preg_replace('/[^\p{Lu}]/u', '', $letters) ?? '';

        return (mb_strlen($upper) / max(1, mb_strlen($letters))) > 0.6;
    }

    /**
     * Relleno de caracteres ("aaaaa", "!!!!!", "$$$$"): no aparece en una
     * opinión escrita por una persona.
     */
    private function hasCharacterFiller(string $text): bool
    {
        return preg_match('/(.)\1{6,}/u', $text) === 1;
    }

    /**
     * Texto que es casi la misma palabra repetida. Los contadores de palabras
     * ignoran mayúsculas, así que se normaliza en minúsculas para que
     * "Excelente EXCELENTE Excelente" cuente como una sola palabra repetida.
     */
    private function hasUppercaseSpam(string $text): bool
    {
        $words = preg_split('/[^\pL]+/u', mb_strtolower($text)) ?: [];
        $words = array_values(array_filter($words, static fn (string $word) => mb_strlen($word) > 2));

        if (count($words) < 4) {
            return false;
        }

        return count(array_unique($words)) / count($words) < 0.4;
    }
}
