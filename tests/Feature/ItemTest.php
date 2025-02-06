<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[TestDox('index正常系 - パラメータ無し')]
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
    #[TestDox('index正常系 - パラメータ有り')]
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
    #[TestDox('index正常系 - kind, image有りデータ')]
    public function indexWithRelation(): void
    {
        $response = $this->getJson('items?limit=2&offset=101');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds, image要素があり、要素1個以上の配列か
            $this->assertNotEmpty($row['kind']);
            $this->assertNotEmpty($row['images']);
        }
    }

    #[Test]
    #[TestDox('index正常系 - kind, image無しデータ')]
    public function indexWithoutRelation(): void
    {
        $response = $this->getJson('items?limit=2&offset=1');
        $response->assertStatus(200);

        $data = $response->json();
        foreach ($data as $row) {
            // kinds, image要素があり、空配列か
            $this->assertEmpty($row['kind']);
            $this->assertEmpty($row['images']);
        }
    }

    #[Test]
    #[TestDox('index異常系 - 不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    public function indexWithInvalidParam($limit, $offset): void
    {
        $response = $this->getJson("items?limit={$limit}&offset={$offset}");
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store正常系 - kind, explanation無し')]
    public function storeWithoutKindExplanation(): void
    {
        $response = $this->postJson('items', ['book_id' => 1, 'name' => 'ハスキー']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'ハスキー']);
    }

    #[Test]
    #[TestDox('store正常系 - kind, explanation有り')]
    public function storeWithKind(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => 'チワワ', 'kind_id' => 1,  'explanation' => '小さいよ。']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'チワワ']);
    }

    #[Test]
    #[TestDox('store正常系 - name文字数ギリギリ')]
    public function storeWithLongName(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => '小さいよ。']);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('store正常系 - explanation文字数ギリギリ')]
    public function storeWithLongExplanation(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => 'チワワ', 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('store異常系 - book_id無し')]
    public function storeWithoutBookId(): void
    {
        $response = $this->postJson('items', ['name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - book_id不正')]
    public function storeWithInvalidBookId(): void
    {
        $response = $this->postJson('items', ['book_id' => 'a', 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - kind_id不正')]
    public function storeWithInvalidKindId(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 'a',  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - name文字数オーバー')]
    public function storeWithOverName(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(101), 'kind_id' => 1,  'explanation' => fake()->realText(1000)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - explanation文字数オーバー')]
    public function storeWithOverExplanation(): void
    {
        $response = $this->postJson('items', ['book_id' => 11, 'name' => fake()->realText(100), 'kind_id' => 1,  'explanation' => fake()->realText(1001)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('show正常系 - kind, image無しデータ')]
    #[TestWith([11])]
    public function showWithoutRelation($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
        $response->assertJsonFragment(['kind' => null]);
        $response->assertJsonFragment(['images' => []]);

    }

    #[Test]
    #[TestDox('show正常系 - kind, image有りデータ')]
    #[TestWith([101])]
    public function showWithRelation($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
        $response->assertJsonPath('kind', fn($kind) => isset($kind['id']) && isset($kind['name']) && isset($kind['book_id']));
        $response->assertJsonPath('images', fn($image) => is_array($image) && count($image) > 0);
    }

    #[Test]
    #[TestDox('show異常系 - 該当データ無し')]
    #[TestWith([10000])]
    public function showWithNoDataParam($id): void
    {
        $response = $this->getJson("items/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('show異常系 - 不正パラメータ')]
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
    #[TestDox('update正常系 - name文字数ギリギリ')]
    public function updateWithLongName(): void
    {
        $response = $this->putJson("items/2", ['name' => fake()->realText(100)]);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('update正常系 - explanation文字数ギリギリ')]
    public function updateWithLongExplanation(): void
    {
        $response = $this->putJson("items/2", ['explanation' => fake()->realText(1000)]);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('update正常系 - パラメータ無し')]
    public function updateWithoutParam(): void
    {
        $response = $this->putJson("items/2");
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('update異常系 - ID不正')]
    public function updateWithInvalidId(): void
    {
        $response = $this->putJson("items/a", ['name' => 'チワワ']);
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('update異常系 - book_id不正')]
    public function updateWithInvalidBookId(): void
    {
        $response = $this->putJson("items/2", ['book_id' => 'a']);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - kind_id不正')]
    public function updateWithInvalidKindId(): void
    {
        $response = $this->putJson("items/2", ['kind_id' => 'a']);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - name文字数オーバー')]
    public function updateWithOverName(): void
    {
        $response = $this->putJson("items/2", ['name' => fake()->realText(101)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - explanation文字数オーバー')]
    public function updateWithOverExplanation(): void
    {
        $response = $this->putJson("items/2", ['explanation' => fake()->realText(1001)]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('destroy正常系')]
    #[TestWith([11])]
    public function destroy($id): void
    {
        $response = $this->deleteJson("items/{$id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id]);
    }

    #[Test]
    #[TestDox('destroy異常系 - 不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function destroyWithInvalidParam($id): void
    {
        $response = $this->deleteJson("items/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('destroy異常系 - 画像がまだある')]
    #[TestWith([101])]
    public function destroyWithImage($id): void
    {
        $response = $this->deleteJson("items/{$id}");
        $response->assertStatus(400);
    }
}
