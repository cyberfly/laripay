<?php

use App\Support\HtmlSanitizer;

test('allows a safe subset of formatting tags', function () {
    $html = '<p>Hello <strong>world</strong>, this is <em>great</em>.</p><ul><li>One</li><li>Two</li></ul>';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
});

test('strips disallowed tags but keeps their text content', function () {
    $result = HtmlSanitizer::sanitize('<script>alert(1)</script><p>Safe <span>text</span></p>');

    expect($result)
        ->not->toContain('<script>')
        ->not->toContain('<span>')
        ->toContain('Safe text');
});

test('strips disallowed attributes from allowed tags', function () {
    $result = HtmlSanitizer::sanitize('<p onclick="alert(1)" style="color:red">Hi</p>');

    expect($result)
        ->not->toContain('onclick')
        ->not->toContain('style')
        ->toContain('<p>Hi</p>');
});

test('removes javascript hrefs from links and forces rel', function () {
    $result = HtmlSanitizer::sanitize('<a href="javascript:alert(1)">click</a>');

    expect($result)
        ->not->toContain('javascript:')
        ->toContain('rel="noopener noreferrer"');
});

test('keeps safe http links', function () {
    $result = HtmlSanitizer::sanitize('<a href="https://example.com">click</a>');

    expect($result)->toContain('href="https://example.com"');
});

test('returns empty string for empty input', function () {
    expect(HtmlSanitizer::sanitize(''))->toBe('');
});
