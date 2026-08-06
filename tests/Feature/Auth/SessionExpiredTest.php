<?php

namespace Tests\Feature\Auth;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SessionExpiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_expired_redirects_web_requests_to_login_with_status(): void
    {
        $request = Request::create('/operaciones/indicadores/dashboard', 'POST');
        $request->headers->set('Accept', 'text/html');

        $response = $this->app->make(ExceptionHandler::class)
            ->render($request, new HttpException(419, 'Page Expired'));

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertSame(
            'Tu sesion expiro. Por seguridad, inicia sesion nuevamente.',
            $response->getSession()->get('status')
        );
    }

    public function test_page_expired_returns_json_for_api_requests(): void
    {
        $request = Request::create('/operaciones/indicadores/dashboard', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $this->app->make(ExceptionHandler::class)
            ->render($request, new HttpException(419, 'Page Expired'));

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame(
            ['message' => 'Tu sesion expiro. Inicia sesion nuevamente.'],
            $response->getData(true)
        );
    }

    public function test_session_expired_error_view_renders_friendly_message(): void
    {
        $html = view('errors.419')->render();

        $this->assertStringContainsString('Tu sesion expiro', $html);
        $this->assertStringContainsString(route('login'), $html);
        $this->assertStringContainsString('Ir al inicio de sesion', $html);
    }
}
