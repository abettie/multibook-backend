<?php

namespace App\Http\Controllers;

use App\Exceptions\DataException;
use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Item;
use App\Models\Kind;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
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
        schema: 'BookWithRelations',
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
        operationId: 'bookIndex',
        tags: ['books'],
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
                        ref: '#/components/schemas/BookWithRelations'
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
        operationId: 'bookStore',
        tags: ['books'],
        summary: '図鑑登録API',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '図鑑名', example: '芸能人図鑑'),
                    new OA\Property(property: 'kinds', type: 'array', items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'name', type: 'string', description: '事務所名', example: 'マセキ芸能社')
                        ]
                    ))
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
        $reqAll = $request->validated();
        $reqBook = [
            'name' => $reqAll['name']
        ];
        $reqKinds = $reqAll['kinds'];
        $res = DB::transaction(function () use ($reqBook, $reqKinds) {
            $book = Book::create($reqBook);
            $book->kinds()->createMany($reqKinds);
            return $book;
        });
        return $this->customStoreResponse($res);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/books/{id}',
        operationId: 'bookShow',
        tags: ['books'],
        summary: '図鑑詳細取得API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BookWithRelations'
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
        operationId: 'bookUpdate',
        tags: ['books'],
        summary: '図鑑更新API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '図鑑名', example: '犬図鑑'),
                    new OA\Property(property: 'kinds', type: 'array', items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'int', description: '事務所ID', example: '1'),
                            new OA\Property(property: 'name', type: 'string', description: '事務所名', example: 'マセキ芸能社')
                        ]
                    ))
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
        $reqAll = $request->validated();
        $reqBook = [
            'name' => $reqAll['name']
        ];
        $reqKinds = $reqAll['kinds'];
        $res = DB::transaction(function () use ($book, $reqBook, $reqKinds) {
            $book->update($reqBook);
            // 種類IDリストを取得
            $dbKindsIdList = $book->kinds()->pluck('id');

            $reqKindsIdList = collect($reqKinds)
                ->filter(fn($reqKind) => isset($reqKind['id']))
                ->map(fn($reqKind) => $reqKind['id']);

            if ($reqKindsIdList->diff($dbKindsIdList)->isNotEmpty()) {
                throw new DataException('更新対象の種類が存在しません');
            }

            $delKindsIdList = $dbKindsIdList->diff($reqKindsIdList);
            // 削除対象の種類IDが使用中かチェック
            if (Item::whereIn('kind_id', $delKindsIdList)->exists()) {
                throw new DataException('削除対象の種類が使用中です');
            }

            // 削除
            Kind::destroy($delKindsIdList->toArray());

            // 更新/新規登録
            foreach ($reqKinds as $reqKind) {
                if ($reqKind['id']) {
                    Kind::updateOrCreate(['id' => $reqKind['id']], $reqKind);
                } else {
                    $book->kinds()->create($reqKind);
                }
            }

            return $book;
        });
        return $this->customUpdateResponse($res);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/books/{id}',
        operationId: 'bookDestroy',
        tags: ['books'],
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
        $res = DB::transaction(function () use ($book) {
            // 削除対象の図鑑IDが使用中かチェック
            if ($book->items()->exists()) {
                throw new DataException('削除対象の図鑑が使用中です');
            }
            $book->kinds()->delete();
            $book->delete();
            return $book;
        });
        return $this->customDestroyResponse($res);
    }
}
