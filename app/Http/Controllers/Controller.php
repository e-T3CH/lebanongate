<?php

declare(strict_types=1);

namespace BMMatic\Http\Controllers;

use BMMatic\Core\App;
use BMMatic\Http\Response;

abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], ?string $layout = null, int $status = 200): Response
    {
        return $this->app->render($template, $data, $layout, $status);
    }

    /** Redirect after a POST (303 See Other). */
    protected function back(string $location): Response
    {
        return Response::redirect($location, 303);
    }

    /** @param array<string, string|int|float> $params */
    protected function t(string $key, array $params = []): string
    {
        return $this->app->translator()->get($key, $params);
    }
}
