<?php

return [
    'ssr' => ['enabled' => false],
    'pages' => [
        'paths' => [resource_path('js/pages')],
        'extensions' => ['js', 'jsx', 'ts', 'tsx'],
    ],
    'testing' => ['ensure_pages_exist' => true],
];
