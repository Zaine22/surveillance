<?php

it('can access swagger ui', function () {
    $response = $this->get(route('l5-swagger.default.api'));

    $response->assertStatus(200);
    $response->assertSee('Surveillance API Documentation');
});

it('can access swagger json', function () {
    $response = $this->get(route('l5-swagger.default.docs'));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'openapi',
        'info' => [
            'title',
            'version',
        ],
        'paths',
    ]);
});
