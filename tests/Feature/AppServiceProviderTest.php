<?php

namespace Tests\Feature;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Repositories\PostRepository;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_repository_interface_is_bound(): void
    {
        $this->assertInstanceOf(PostRepository::class, app(PostRepositoryInterface::class));
    }

    public function test_force_https_switches_url_scheme(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/'));

        URL::forceScheme('http');
    }

    public function test_force_https_config_key_exists_and_defaults_to_false_for_http_url(): void
    {
        $this->assertFalse(config('app.force_https'));
    }
}
