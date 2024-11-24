<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Item;
use App\Models\Image;
use App\Models\Kind;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        $books = Book::factory(3)->create();
        $kinds = Kind::factory(15)->recycle($books)->create();
        $items = Item::factory(30)->recycle([$books, $kinds])->create();
        $images = Image::factory(150)->recycle($items)->create();
    }
}
