<?php

namespace App\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** Serve files from public/legacy/ when the host does not map them as static assets. */
final class LegacyPublicFileResponse
{
    public static function route(): \Closure
    {
        return static function (string $path): Response|SymfonyResponse {
            return self::serve($path);
        };
    }

    public static function serve(string $path): Response|SymfonyResponse
    {
        if (str_contains($path, '..')) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        $base = realpath(public_path('legacy'));
        $full = public_path('legacy/'.$path);
        $real = is_file($full) ? realpath($full) : false;

        if ($base === false || $real === false || ! str_starts_with($real, $base)) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        $headers = [
            'Cache-Control' => 'public, max-age=0, must-revalidate',
        ];

        $mime = match (true) {
            str_ends_with($path, '.css') => 'text/css; charset=utf-8',
            str_ends_with($path, '.js') => 'application/javascript; charset=utf-8',
            str_ends_with($path, '.html') => 'text/html; charset=utf-8',
            default => null,
        };
        if ($mime !== null) {
            $headers['Content-Type'] = $mime;
        }

        $contents = file_get_contents($real);
        if ($contents === false) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        return response($contents, SymfonyResponse::HTTP_OK, $headers);
    }
}
