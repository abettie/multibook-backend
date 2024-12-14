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
    use RefreshDatabase;

    #[Test]
    #[TestDox('index正常系：パラメータ無し')]
    public function indexWithoutParameters(): void
    {
        $response = $this->getJson('books');
        $response->assertStatus(200);
        // 10個(limitデフォルト値)あるか
        $response->assertJsonCount(10);

        $data = $response->json();
        // 1番目(offsetデフォルト値)から取ってきているか
        $this->assertSame(1, $data[0]['id']);
    }

    #[Test]
    #[TestDox('index正常系：パラメータ有り')]
    public function indexWithParameters(): void
    {
        $response = $this->getJson('books?limit=10&offset=4');
        $response->assertStatus(200);
        // 10個あるか
        $response->assertJsonCount(10);

        $data = $response->json();
        // 5番目から取ってきているか
        $this->assertSame(5, $data[0]['id']);
    }

    #[Test]
    #[TestDox('index正常系：kinds有りデータ')]
    public function indexWithKindsData(): void
    {
        $response = $this->getJson('books?limit=2&offset=11');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、要素1個以上の配列か
            $this->assertTrue(isset($row['kinds']));
            $this->assertNotEmpty($row['kinds']);
        }
    }

    #[Test]
    #[TestDox('index正常系：kinds無しデータ')]
    public function indexWithoutKindsData(): void
    {
        $response = $this->getJson('books?limit=2&offset=1');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、空配列か
            $this->assertTrue(isset($row['kinds']));
            $this->assertEmpty($row['kinds']);
        }
    }

    #[Test]
    #[TestDox('index異常系：不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    public function indexWithInvalidParam($limit, $offset): void
    {
        $response = $this->getJson("books?limit={$limit}&offset={$offset}");
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store正常系')]
    public function store(): void
    {
        $response = $this->postJson('books', ['name' => '犬図鑑']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => '犬図鑑']);
    }

    #[Test]
    #[TestDox('store異常系：不正パラメータ')]
    public function storeWithInvalidParam(): void
    {
        $response = $this->postJson('books', ['name' => fake()->realText(50)]);
        $response->assertStatus(200);
        $response = $this->postJson('books', ['name' => fake()->realText(51)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('show正常系')]
    #[TestWith([1])]
    #[TestWith([11])]
    #[TestWith([30])]
    public function show($id): void
    {
        $response = $this->getJson("books/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('show異常系：不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function showWithInvalidParam($id): void
    {
        $response = $this->getJson("books/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('update正常系')]
    #[TestWith([1, '図鑑1'])]
    #[TestWith([11, '図鑑11'])]
    #[TestWith([30, '図鑑30'])]
    public function update($id, $name): void
    {
        $response = $this->putJson("books/{$id}", ['name' => $name]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('update異常系：不正パラメータ')]
    public function updateWithInvalidParam(): void
    {
        $response = $this->putJson("books/a", ['name' => '図鑑a']);
        $response->assertStatus(404);
        $response = $this->putJson("books/2", ['name' => fake()->realText(50)]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => 2]);
        $response = $this->putJson("books/2", ['name' => fake()->realText(51)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('destroy正常系')]
    #[TestWith([1])]
    #[TestWith([11])]
    #[TestWith([30])]
    public function destroy($id): void
    {
        $response = $this->deleteJson("books/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('destroy異常系：不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function destroyWithInvalidParam($id): void
    {
        $response = $this->deleteJson("books/{$id}");
        $response->assertStatus(404);
    }
}
