<?php

namespace App\Http;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** Run a script under public/legacy/ when the host routes all .php through index.php. */
final class LegacyScriptResponse
{
    public static function route(string $filename): \Closure
    {
        return static fn (): Response => self::run($filename);
    }

    public static function run(string $filename): Response
    {
        $base = realpath(public_path('legacy'));
        $path = realpath(public_path('legacy/'.$filename));

        if ($base === false || $path === false || ! str_starts_with($path, $base)) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        chdir(dirname($path));
        ob_start();
        require $path;
        $body = ob_get_clean() ?? '';

        $status = http_response_code();
        if ($status === false || $status === 0) {
            $status = SymfonyResponse::HTTP_OK;
        }

        return response($body, $status, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
