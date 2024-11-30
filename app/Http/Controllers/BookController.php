<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\BookStoreRequest;
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
        // GETパラメータのlimitとoffsetを取得
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 0);

        $result = Book::offset((int)$offset)
            ->limit((int)$limit)
            ->with('kinds')
            ->get();

        return $this->customIndexResponse($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookStoreRequest $request)
    {
        $result = Book::create($request->validated());

        return $this->customStoreResponse($result);
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
