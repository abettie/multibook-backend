<?php

namespace Tests\Feature\Models;

use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    private Image $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->image = Image::find(10);
    }

    #[Test]
    #[TestDox('index正常系 - パラメータ無し')]
    public function indexWithoutParameters(): void
    {
        $response = $this->getJson('images');
        $response->assertStatus(200);
        // 10個(limitデフォルト値)あるか
        $response->assertJsonCount(10);
        // 1番目(offsetデフォルト値)から取ってきているか
        $response->assertJsonPath('0.id', 1);
    }

    #[Test]
    #[TestDox('index正常系 - パラメータ有り')]
    public function indexWithParameters(): void
    {
        $response = $this->getJson('images?limit=20&offset=4');
        $response->assertStatus(200);
        $response->assertJsonCount(20);
        $response->assertJsonPath('0.id', 5);
    }

    #[Test]
    #[TestDox('index異常系 - 不正パラメータ')]
    #[TestWith(['a', 11])]
    #[TestWith([5, 'a'])]
    #[TestWith(['a', 'a'])]
    public function indexWithInvalidParam($limit, $offset): void
    {
        $response = $this->getJson("images?limit={$limit}&offset={$offset}");
        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store正常系')]
    public function store(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post('images', [
            'item_id' => 1,
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('item_id', 1);
        $fileName = basename($response->json('file_name'));
        $this->assertTrue(Storage::disk('s3')->exists('images/' . $fileName));
    }

    #[Test]
    #[TestDox('store異常系 - 画像ファイル容量オーバー')]
    public function storeWithOverImage(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->create('test.jpg', 8001);

        $response = $this->postJson('images', [
            'item_id' => 1,
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - image無し')]
    public function storeWithoutImage(): void
    {
        $response = $this->postJson('images', [
            'item_id' => 1,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - imageが文字列')]
    public function storeWithStringImage(): void
    {
        $response = $this->postJson('images', [
            'item_id' => 1,
            'image' => 'hoge',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - imageがExcel')]
    public function storeWithExcelImage(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->create('test.xlsx', 200);

        $response = $this->postJson('images', [
            'item_id' => 1,
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - item_id無し')]
    public function storeWithoutItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('images', [
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - item_id不正')]
    public function storeWithInvalidItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('images', [
            'item_id' => 'a',
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('store異常系 - 存在しないitem_id')]
    public function storeWithNoExistItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('images', [
            'item_id' => 10000,
            'image' => $file,
        ]);

        $response->assertStatus(400);
    }

    #[Test]
    #[TestDox('show正常系')]
    public function show(): void
    {
        $response = $this->getJson('/images/10');
        $response->assertStatus(200);
    }

    #[Test]
    #[TestDox('show異常系 - 該当データ無し')]
    #[TestWith([10000])]
    public function showWithNoDataParam($id): void
    {
        $response = $this->getJson("images/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('show異常系 - 不正パラメータ')]
    #[TestWith(['a'])]
    #[TestWith(['.'])]
    #[TestWith(['-'])]
    public function showWithInvalidParam($id): void
    {
        $response = $this->getJson("images/{$id}");
        $response->assertStatus(404);
    }

    #[Test]
    #[TestDox('update正常系')]
    public function update(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => $this->image->item_id,
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('item_id', $this->image->item_id);
        $fileName = basename($response->json('file_name'));
        $this->assertTrue(Storage::disk('s3')->exists('images/' . $fileName));
    }

    #[Test]
    #[TestDox('update異常系 - 画像ファイル容量オーバー')]
    public function updateWithOverImage(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->create('test.jpg', 8001);

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => $this->image->item_id,
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - image無し')]
    public function updateWithoutImage(): void
    {
        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => $this->image->item_id,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - imageが文字列')]
    public function updateWithStringImage(): void
    {
        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => $this->image->item_id,
            'image' => 'hoge',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - imageがExcel')]
    public function updateWithExcelImage(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->create('test.xlsx', 200);

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => $this->image->item_id,
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - item_id無し')]
    public function updateWithoutItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - item_id不正')]
    public function updateWithInvalidItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => 'a',
            'image' => $file,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    #[TestDox('update異常系 - item_id変更')]
    public function updateWithModifyItemId(): void
    {
        Storage::fake('s3');
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('updateImages/'. $this->image->id, [
            'item_id' => 1,
            'image' => $file,
        ]);

        $response->assertStatus(400);
    }

    #[Test]
    #[TestDox('destroy正常系')]
    #[TestWith([11])]
    public function destroy($id): void
    {
        Storage::fake('s3');
        $response = $this->deleteJson("images/{$id}");
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
        $response = $this->deleteJson("images/{$id}");
        $response->assertStatus(404);
    }
}