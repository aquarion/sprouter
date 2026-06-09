<?php

test('home page renders for guests', function () {
    $response = $this->withoutVite()->get(route('home'));
    $response->assertOk();
});
