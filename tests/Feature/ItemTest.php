<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[TestDox('index正常系：パラメータ無し')]
    public function indexWithoutParameters(): void
    {
        $response = $this->getJson('items');
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
        $response = $this->getJson('items?limit=10&offset=4');
        $response->assertStatus(200);
        // 10個あるか
        $response->assertJsonCount(10);

        $data = $response->json();
        // 5番目から取ってきているか
        $this->assertSame(5, $data[0]['id']);
    }

    #[Test]
    #[TestDox('index正常系：kind有りデータ')]
    public function indexWithKindsData(): void
    {
        $response = $this->getJson('items?limit=2&offset=51');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、要素1個以上の配列か
            $this->assertNotEmpty($row['kind']);
        }
    }

    #[Test]
    #[TestDox('index正常系：kind無しデータ')]
    public function indexWithoutKindsData(): void
    {
        $response = $this->getJson('items?limit=2&offset=1');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds要素があり、空配列か
            $this->assertEmpty($row['kind']);
        }
    }

    #[Test]
    #[TestDox('index異常系：不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    public function indexWithInvalidParam($limit, $offset): void
    {
        $response = $this->getJson("items?limit={$limit}&offset={$offset}");
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store正常系：kind無し')]
    public function storeWithoutKind(): void
    {
        $response = $this->postJson('items', ['book_id' => 1, 'name' => 'ハスキー', 'explanation' => '大きいよ。']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'ハスキー']);
    }

    #[Test]
    #[TestDox('store正常系：kind有り')]
    public function storeWithKind(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => 'チワワ', 'kind_id' => 1,  'explanation' => '小さいよ。']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'チワワ']);
    }

    #[Test]
    #[TestDox('store異常系：不正パラメータ')]
    public function storeWithInvalidParam(): void
    {
        // 閾値ギリギリ
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(200);
        // book_id不正
        $response = $this->postJson('items', ['book_id' => 'a', 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
        // kind_id不正
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 'a',  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
        // name閾値オーバー
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(101), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
        // explanation閾値オーバー
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1001)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('show正常系')]
    #[TestWith([1])]
    #[TestWith([11])]
    #[TestWith([100])]
    public function show($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('show異常系：該当データ無し')]
    #[TestWith([10000])]
    public function showWithNoDataParam($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('show異常系：不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function showWithInvalidParam($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('update正常系')]
    #[TestWith([11, 2, 'ハスキー', null, '大きいよ。'])]
    #[TestWith([300, 3, 'セントバーナード', 5, 'とても大きいよ。'])]
    public function update($id, $book_id, $name, $kind_id, $explanation): void
    {
        $response = $this->putJson("items/{$id}", ['book_id' => $book_id, 'name' => $name, 'kind_id' => $kind_id, 'explanation' => $explanation]);
        $response->assertStatus(200);
        $response = $this->putJson("items/{$id}", ['book_id' => $book_id]);
        $response->assertStatus(200);
        $response = $this->putJson("items/{$id}", ['name' => $name]);
        $response->assertStatus(200);
        $response = $this->putJson("items/{$id}", ['kind_id' => $kind_id]);
        $response->assertStatus(200);
        $response = $this->putJson("items/{$id}", ['explanation' => $explanation]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('update異常系：不正パラメータ')]
    public function updateWithInvalidParam(): void
    {
        $response = $this->putJson("items/a", ['name' => 'チワワ']);
        $response->assertStatus(404);
        $response = $this->putJson("items/2", ['name' => fake()->realText(100)]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => 2]);
        $response = $this->putJson("items/2", ['name' => fake()->realText(101)]);
        $response->assertStatus(422);
        $response = $this->putJson("items/2", ['explanation' => fake()->realText(1000)]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => 2]);
        $response = $this->putJson("items/2", ['explanation' => fake()->realText(1001)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('destroy正常系')]
    #[TestWith([1])]
    #[TestWith([11])]
    #[TestWith([300])]
    public function destroy($id): void
    {
        $response = $this->deleteJson("items/{$id}");
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
        $response = $this->deleteJson("items/{$id}");
        $response->assertStatus(404);
    }
}
