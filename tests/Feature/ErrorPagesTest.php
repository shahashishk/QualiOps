<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_403_page_is_user_friendly(): void
    {
        $pm = User::factory()->create();
        $pm->assignRole('PM');

        $response = $this->actingAs($pm)->get(route('system-maintenance.index'));

        $response->assertForbidden();
        $response->assertSee('Access Forbidden');
    }

    public function test_404_page_is_user_friendly(): void
    {
        $response = $this->get('/missing-page-for-error-test');

        $response->assertNotFound();
        $response->assertSee('Page Not Found');
    }

    public function test_419_page_is_user_friendly(): void
    {
        config()->set('app.debug', false);

        $request = Request::create('/csrf-expired');
        $response = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, new TokenMismatchException());

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('Session Expired', $response->getContent());
    }

    public function test_500_page_is_user_friendly_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);

        Route::get('/error-test-500', function () {
            throw new \RuntimeException('Test server error');
        });

        $response = $this->get('/error-test-500');

        $response->assertStatus(500);
        $response->assertSee('Unexpected Server Error');
    }
}
