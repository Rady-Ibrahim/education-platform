<?php

namespace Tests\Unit;

use App\Support\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_payload_shape(): void
    {
        $response = ApiResponse::success(['id' => 1], 'done');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'done',
            'data' => ['id' => 1],
            'meta' => [],
        ], $response->getData(true));
    }

    public function test_error_payload_shape(): void
    {
        $response = ApiResponse::error('fail', 422, ['field' => ['مطلوب']]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'fail',
            'errors' => ['field' => ['مطلوب']],
            'meta' => [],
        ], $response->getData(true));
    }
}
