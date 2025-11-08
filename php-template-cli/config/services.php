<?php

return [
    'services' => [
        'template.processor' => [
            'class' => \App\Template\Processor::class,
            'arguments' => [
                '%template.directory%',
                '@utils.variables_resolver',
            ],
        ],
        'utils.variables_resolver' => [
            'class' => \App\Utils\VariablesResolver::class,
        ],
    ],
    'parameters' => [
        'template.directory' => '/template/packs',
    ],
];