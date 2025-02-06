<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Item;
use App\Models\Image;
use App\Models\Kind;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $books_no_kinds = Book::factory(10)->create();
        $books = Book::factory(20)->create();
        $books_no_items = Book::factory(10)->create();
        $kinds = Kind::factory(150)->recycle($books)->create();
        $items_no_kinds = Item::factory(50)->recycle([$books_no_kinds])->noKind()->create();
        $items_no_images = Item::factory(50)->recycle([$books, $kinds])->create();
        $items = Item::factory(200)->recycle([$books, $kinds])->create();
        $images = Image::factory(1000)->recycle($items)->create();
    }
}
