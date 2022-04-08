<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Comment and Comment Counter Table
    |--------------------------------------------------------------------------
    |
    | This option controls the default comment and counter table name.
    |
    */

    'comment_table' => 'commentable_comments',
    'counter_table' => 'commentable_comment_counter',

    /*
    |--------------------------------------------------------------------------
    | Verifying controller
    |--------------------------------------------------------------------------
    |
    | This option controls whether a comment should be verified before publishing or not.
    |
    */

    'should_be_verified' => false,

    /*
    |--------------------------------------------------------------------------
    | Allowing to comment controller
    |--------------------------------------------------------------------------
    |
    | This option controls whether a unregistered user can comment or not.
    |
    */

    'user_should_be_registered' => true,

    /*
    |--------------------------------------------------------------------------
    | Allowing to censot the comment
    |--------------------------------------------------------------------------
    |
    | This option controls whether a comment should be censored or not.
    |
    */

    'censorship' => [
        'should_be_censored' => false,
        'break' => false
    ],


    // Allow to comment a comment (sub awnser)
    'can_comment_a_comment'  => false,

    // Enable the API
    'enable_api' => true,

    // Route API
    'route_api' => '/api/comments',

    // For API => paginate value
    'paginate' => 15,


    // You can add middlewares for API routes (example : auth:api
    'api_middleware_get' => [],
    'api_middleware_create' => [],
    'api_middleware_delete' => [],
];
