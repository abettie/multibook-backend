<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Book;
use App\Models\Kind;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'name' => fake()->word().'アイテム',
            'kind_id' => Kind::factory(),
            'explanation' => fake()->sentence(5),
        ];
    }

    /**
     * kindの無いItemを生成
     * 
     * @return static The current factory instance with the custom state applied.
     */
    public function noKind(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'kind_id' => null,
            ];
        });
    }
}
