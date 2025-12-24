<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
  public function render($request, Throwable $e)
  {
    if ($e instanceof DomainException) {
      return response()->json([
        'message' => $e->getMessage(),
      ], 422);
    }

    return parent::render($request, $e);
  }
}