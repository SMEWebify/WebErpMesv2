<?php

namespace App\Http\Middleware;

use App\Services\Audit\AccessLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trace la consultation d'une fiche sensible : `audit.access:{type},{paramètre de route}`,
 * par exemple `audit.access:quote,id`.
 *
 * Rien n'est fait pendant la requête : l'écriture a lieu dans terminate(), une fois la
 * réponse envoyée, et seulement pour une consultation réussie (2xx) d'un utilisateur
 * connecté. Une erreur d'écriture est journalisée, jamais remontée à l'utilisateur.
 */
class LogReadAccess
{
    public function handle(Request $request, Closure $next, string $type, string $parameter = 'id'): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! AccessLogger::enabled() || ! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return;
        }

        $user = $request->user();
        $route = $request->route();
        if (! $user || ! $route) {
            return;
        }

        // Les paramètres du middleware ne sont pas repassés à terminate() : on les relit
        // sur la route.
        [$type, $parameter] = $this->arguments($route->gatherMiddleware());
        if ($type === null) {
            return;
        }

        $subject = $route->parameter($parameter);
        if ($subject === null) {
            return;
        }

        try {
            app(AccessLogger::class)->record($user, $type, $subject, $route->getName(), $request->ip());
        } catch (\Throwable $e) {
            Log::warning('Journal des consultations : écriture impossible', [
                'route' => $route->getName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $middleware
     * @return array{0: ?string, 1: string}
     */
    private function arguments(array $middleware): array
    {
        foreach ($middleware as $entry) {
            if (is_string($entry) && str_starts_with($entry, 'audit.access:')) {
                $args = explode(',', substr($entry, strlen('audit.access:')));

                return [$args[0] ?: null, $args[1] ?? 'id'];
            }
        }

        return [null, 'id'];
    }
}
