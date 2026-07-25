<?php

test('returns a successful response', function () {
    $response = $this->get(route('shop.index'));

    $response->assertOk();
});
