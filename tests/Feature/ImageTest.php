<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ImageTest extends TestCase
{
    #[Test]
    #[TestDox('正常系：サンプルテスト')]
    public function sample(): void
    {
        $response = $this->json('GET', '/');

        $response->assertStatus(200);
    }
}
