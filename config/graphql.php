<?php

return [
    'auth_secret_key' => getenv('GRAPHQL_JWT_AUTH_SECRET_KEY'),
    'graphql_dev_mode' => false,
    'max_query_complexity' => 0,
    'max_query_depth' => 0,
    'disabling_introspection' => false,
    'auth_server_url' => '',
    'corsOrigins' => [
    ]
];