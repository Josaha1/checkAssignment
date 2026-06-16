<?php

// Render terminate TLS ที่ edge แล้วส่ง X-Forwarded-Proto: https เข้า container
// ถ้าไม่ trust proxy → Laravel คิดว่า http → asset/redirect หลุดเป็น http (Mixed Content)
test('trusts proxy X-Forwarded-Proto and generates https urls', function () {
    $response = $this->get('/', ['X-Forwarded-Proto' => 'https']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://');
});
