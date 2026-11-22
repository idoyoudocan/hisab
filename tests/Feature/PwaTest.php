<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_file_is_valid_and_complete(): void
    {
        $path = public_path('manifest.json');

        $this->assertFileExists($path);

        $manifest = json_decode(file_get_contents($path), true);
        $this->assertIsArray($manifest, 'manifest.json must be valid JSON');
        $this->assertSame('হিসাব — দোকানের হিসাব-নিকাশ', $manifest['name']);
        $this->assertSame('হিসাব', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#0E3B2E', $manifest['theme_color']);
        $this->assertSame('#0B1F17', $manifest['background_color']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);

        $icons = collect($manifest['icons'])->keyBy('sizes');
        $this->assertArrayHasKey('192x192', $icons);
        $this->assertArrayHasKey('512x512', $icons);
        $this->assertSame('any', $icons['192x192']['purpose']);
        $this->assertStringContainsString('maskable', $icons['512x512']['purpose']);
    }

    public function test_service_worker_file_has_fetch_handler_and_skips_livewire(): void
    {
        $path = public_path('sw.js');

        $this->assertFileExists($path);

        $sw = file_get_contents($path);
        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString("addEventListener('install'", $sw);
        $this->assertStringContainsString("addEventListener('activate'", $sw);
        $this->assertStringContainsString('hisab-v3', $sw);
        $this->assertStringContainsString('/livewire/', $sw);
        $this->assertStringContainsString('ইন্টারনেট সংযোগ নেই', $sw);
    }

    public function test_icons_exist_with_correct_dimensions(): void
    {
        $this->assertIcon(public_path('icons/icon-192.png'), 192, 192);
        $this->assertIcon(public_path('icons/icon-512.png'), 512, 512);
        $this->assertIcon(public_path('icons/apple-touch-icon.png'), 180, 180);
    }

    public function test_home_page_links_manifest_and_theme_color(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('<meta name="theme-color" content="#0E3B2E">', false);
        $response->assertSee('pwa-banner', false);
        $response->assertSee('ইনস্টল করুন', false);
    }

    public function test_app_layout_links_manifest_and_registers_service_worker(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('<meta name="theme-color" content="#0E3B2E">', false);
        $response->assertSee("serviceWorker.register('/sw.js')", false);
        $response->assertSee('beforeinstallprompt', false);
    }

    private function assertIcon(string $path, int $width, int $height): void
    {
        $this->assertFileExists($path);
        $size = getimagesize($path);
        $this->assertNotFalse($size, "$path must be a valid image");
        $this->assertSame(IMAGETYPE_PNG, $size[2], "$path must be a PNG");
        $this->assertSame($width, $size[0]);
        $this->assertSame($height, $size[1]);
    }
}
