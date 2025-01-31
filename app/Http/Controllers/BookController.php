<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use OpenApi\Attributes as OA;

class BookController extends BaseController
{
    #[OA\Schema(
        schema: 'Book',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: '犬図鑑')
        ]
    )]
    #[OA\Schema(
        schema: 'Kind',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'book_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: '大型犬')
        ]
    )]
    #[OA\Schema(
        schema: 'BookWithKinds',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: '犬図鑑'),
            new OA\Property(property: 'kinds', type: 'array', items: new OA\Items(ref: '#/components/schemas/Kind'))
        ]
    )]

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/books',
        operationId: 'index',
        summary: '図鑑一覧取得API',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, description: '取得件数', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'offset', in: 'query', required: false, description: '取得開始位置', schema: new OA\Schema(type: 'integer', default: 0))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/BookWithKinds'
                    )
                )
            )
        ]
    )]
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
    #[OA\Post(
        path: '/books',
        operationId: 'store',
        summary: '図鑑登録API',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '図鑑名', example: '犬図鑑')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Book'
                )
            )
        ]
    )]
    public function store(BookStoreRequest $request)
    {
        $result = Book::create($request->validated());

        return $this->customStoreResponse($result);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/books/{id}',
        operationId: 'show',
        summary: '図鑑詳細取得API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BookWithKinds'
                )
            )
        ]
    )]
    public function show(Book $book)
    {
        $book->load('kinds');
        return $this->customShowResponse($book);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/books/{id}',
        operationId: 'update',
        summary: '図鑑更新API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '図鑑名', example: '犬図鑑')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Book'
                )
            )
        ]
    )]
    public function update(BookUpdateRequest $request, Book $book)
    {
        $book->update($request->validated());

        return $this->customUpdateResponse($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/books/{id}',
        operationId: 'destroy',
        summary: '図鑑削除API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Book'
                )
            )
        ]
    )]
    public function destroy(Book $book)
    {
        $book->delete();

        return $this->customDestroyResponse($book);
    }
}
