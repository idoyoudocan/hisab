<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeployRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('DEPLOY_TOKEN=test-token');
        $_ENV['DEPLOY_TOKEN'] = 'test-token';
        $_SERVER['DEPLOY_TOKEN'] = 'test-token';
    }

    protected function tearDown(): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);
        parent::tearDown();
    }

    public function test_migrate_without_token_returns_404(): void
    {
        $this->postJson('/deploy/migrate')->assertNotFound();
    }

    public function test_migrate_with_wrong_token_returns_404(): void
    {
        $this->postJson('/deploy/migrate', [], ['X-Deploy-Token' => 'wrong'])
            ->assertNotFound();
    }

    public function test_migrate_with_correct_token_succeeds(): void
    {
        // No session/CSRF middleware on this route: it must work even
        // when the sessions table does not exist yet (fresh deploy).
        $this->postJson('/deploy/migrate', [], ['X-Deploy-Token' => 'test-token'])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
