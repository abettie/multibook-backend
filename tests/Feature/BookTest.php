<?php

namespace Tests\Feature\Models;

use App\Models\Book;
use App\Models\Item;
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

    private Book $book;
    private $kinds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->book = Book::find(15);
        $this->kinds = $this->book->kinds->pluck('id')->map(fn($id) => ['id' => $id, 'name' => '種類' . $id]);
    }

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
            $this->assertEmpty($row['kinds']);
        }
    }

    #[Test]
    #[TestDox('index異常系：不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    #[TestWith(['a', 'a'])]
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
    #[TestDox('show異常系：該当データ無し')]
    #[TestWith([10000])]
    public function showWithNoDataParam($id): void
    {
        $response = $this->getJson("books/{$id}");
        $response->assertStatus(404);
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
    public function update(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => '単体テスト', 'kinds' => $this->kinds->toArray()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $this->book->id]);
    }

    #[Test]
    #[TestDox('update正常系 - name桁数ギリギリ')]
    public function updateWithLongName(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => fake()->realText(50), 'kinds' => $this->kinds->toArray()]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $this->book->id]);
    }

    #[Test]
    #[TestDox('update正常系 - kinds追加')]
    public function updateWithAddKinds(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => '単体テスト', 'kinds' => $this->kinds->collect()->push(['name' => '新規種類'])->toArray()]);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('update正常系 - kinds削除')]
    public function updateWithDeleteKinds(): void
    {
        // kinds最後の要素のID取得
        $lastKindId = $this->kinds->collect()->last()['id'];
        // 該当のkindを設定しているItemを削除
        Item::where('kind_id', $lastKindId)->delete();
        // kinds削除
        $kindsWithoutLast = $this->kinds->collect()->filter(fn($kind) => $kind['id'] !== $lastKindId)->toArray();
        $response = $this->putJson("books/{$this->book->id}", ['name' => '単体テスト', 'kinds' => $kindsWithoutLast]);
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('update異常系 - id無し')]
    public function updateWithoutId(): void
    {
        $response = $this->putJson("books", ['name' => '図鑑a', 'kinds' => $this->kinds->toArray()]);
        $response->assertStatus(405);
    }

    #[Test]
    #[TestDox('update異常系 - id不正')]
    public function updateWithInvalidId(): void
    {
        $response = $this->putJson("books/a", ['name' => '図鑑a', 'kinds' => $this->kinds->toArray()]);
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('update異常系 - name無し')]
    public function updateWithoutName(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['kinds' => $this->kinds->toArray()]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - name桁数オーバー')]
    public function updateWithOverName(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => fake()->realText(51), 'kinds' => $this->kinds->toArray()]);
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - kindsのid不正')]
    public function updateWithInvalidKindsId(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => fake()->realText(50), 'kinds' => ['id' => 'a', 'name' => '種類a']]); 
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - bookと紐づかないkindを指定')]
    public function updateWithNotRelatedKinds(): void
    {
        $response = $this->putJson("books/{$this->book->id}", ['name' => fake()->realText(50), 'kinds' => ['id' => 1, 'name' => '種類1']]); 
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - kindsのname無し')]
    public function updateWithoutKindsName(): void
    {
        $kindsWithoutName = $this->kinds->toArray();
        unset($kindsWithoutName[0]['name']);
        $response = $this->putJson("books/{$this->book->id}", ['name' => fake()->realText(50), 'kinds' => $kindsWithoutName]); 
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - id以外のパラメータ無し')]
    public function updateWithoutPutParam(): void
    {
        $response = $this->putJson("books/{$this->book->id}");
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
    #[TestDox('destroy異常系 - 不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function destroyWithInvalidParam($id): void
    {
        $response = $this->deleteJson("books/{$id}");
        $response->assertStatus(404);
    }
}
