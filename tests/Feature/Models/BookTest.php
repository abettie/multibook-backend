<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookTest extends TestCase
{
    #[Test]
    #[TestDox('正常系：パラメータ無し')]
    public function withoutParameters(): void
    {
        $response = $this->json('GET', 'books');
        $response->assertStatus(200);
        // 10個(limitデフォルト値)あるか
        $response->assertJsonCount(10);

        $data = $response->json();
        // 1番目(offsetデフォルト値)から取ってきているか
        $this->assertSame(1, $data[0]['id']);
    }

    #[Test]
    #[TestDox('正常系：パラメータ有り')]
    public function withParameters(): void
    {
        $response = $this->json('GET', 'books?limit=10&offset=4');
        $response->assertStatus(200);
        // 10個あるか
        $response->assertJsonCount(10);

        $data = $response->json();
        // 5番目から取ってきているか
        $this->assertSame(5, $data[0]['id']);
    }

    #[Test]
    #[TestDox('正常系：kinds有りデータ')]
    public function withKindsData(): void
    {
        $response = $this->json('GET', 'books?limit=2&offset=11');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、要素1個以上の配列か
            $this->assertTrue(isset($row['kinds']));
            $this->assertNotEmpty($row['kinds']);
        }
    }

    #[Test]
    #[TestDox('正常系：kinds無しデータ')]
    public function withoutKindsData(): void
    {
        $response = $this->json('GET', 'books?limit=2&offset=1');

        $response->assertStatus(200);
        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、空配列か
            $this->assertTrue(isset($row['kinds']));
            $this->assertEmpty($row['kinds']);
        }
    }

    #[Test]
    #[TestDox('異常系：不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    public function withInvalidParam($limit, $offset): void
    {
        $response = $this->json('GET', "books?limit={$limit}&offset={$offset}");

        $response->assertStatus(422);
    }
}
