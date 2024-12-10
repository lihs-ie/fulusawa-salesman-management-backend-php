<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * ヘルスチェックコントローラ.
 */
class HealthCheckController extends Controller
{
    /**
     * ヘルスチェック.
     */
    public function check()
    {
        return new Response(['status' => 'ok'], Response::HTTP_OK);
    }
}
