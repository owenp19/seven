<?php

namespace App\Swagger;

use L5Swagger\ConfigFactory;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use L5Swagger\SecurityDefinitions;

/**
 * SevenGeneratorFactory
 *
 * Factoría que entrega SevenGenerator (analyser con docblocks + atributos)
 * en lugar del generator base del paquete. Se rebinde GeneratorFactory en
 * el contenedor desde AppServiceProvider::register().
 */
class SevenGeneratorFactory extends GeneratorFactory
{
    private readonly ConfigFactory $configFactory;

    public function __construct(ConfigFactory $configFactory)
    {
        parent::__construct($configFactory);

        $this->configFactory = $configFactory;
    }

    public function make(string $documentation): Generator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        return new SevenGenerator(
            $config['paths'],
            $config['constants'] ?? [],
            $config['generate_yaml_copy'] ?? false,
            new SecurityDefinitions(
                $config['securityDefinitions']['securitySchemes'] ?? [],
                $config['securityDefinitions']['security'] ?? [],
            ),
            $config['scanOptions'] ?? [],
        );
    }
}
