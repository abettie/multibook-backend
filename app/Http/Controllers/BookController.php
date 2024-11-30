<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(BookIndexRequest $request)
    {
        // クエリビルダーを初期化
        $query = Book::query();

        // GETパラメータのlimitとoffsetを取得
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if (!is_null($offset)) {
            $query->offset((int)$offset);
        }

        if (!is_null($limit)) {
            $query->limit((int)$limit);
        }

        $result = $query->get();

        $body = [];
        foreach ($result as $data) {
            $row = [];
            $row['id'] = $data['id'];
            $row['name'] = $data['name'];
            $kinds = $data->kinds()->get();
            $row['kinds'] = [];
            foreach ($kinds as $kind) {
                $row['kinds'][] = [
                    'id' => $kind['id'],
                    'name' => $kind['name'],
                ];
            }
            $body[] = $row;
        }

        return $this->customApiResponse($body);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }
}
