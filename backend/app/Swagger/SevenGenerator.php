<?php

namespace App\Swagger;

use L5Swagger\Generator;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Generator as OpenApiGenerator;

/**
 * SevenGenerator
 *
 * Subclase del generator de l5-swagger que configura el analyser de
 * swagger-php con soporte de docblocks (@OA\...) Y atributos PHP 8.
 *
 * Necesario porque `php artisan config:cache` no puede serializar
 * instancias de objetos, así que `config/l5-swagger.php` declara
 * `analyser => null` y aquí se entrega la instancia al generarse el spec
 * (único momento en que se usa), nunca por request.
 */
class SevenGenerator extends Generator
{
    protected function setAnalyser(OpenApiGenerator $generator): void
    {
        $generator->setAnalyser(new ReflectionAnalyser([
            new AttributeAnnotationFactory,
            new DocBlockAnnotationFactory,
        ]));
    }
}
