<?php

namespace App\Http\Controllers;

use App\Exceptions\DataException;
use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Http\Requests\BookUpsertThumbnailRequest;
use App\Models\Book;
use App\Models\Item;
use App\Models\Kind;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class BookController extends BaseController
{
    #[OA\Schema(
        schema: 'Book',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: '犬図鑑'),
            new OA\Property(property: 'thumbnail', type: 'string', example: 'https://example.com/thumbnails/d2f3c4e5-6a7b-8c9d-0e1f-2g3h4i5j6k7l.jpg'),
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
            new OA\Property(property: 'thumbnail', type: 'string', example: 'https://example.com/thumbnails/d2f3c4e5-6a7b-8c9d-0e1f-2g3h4i5j6k7l.jpg'),
            new OA\Property(property: 'kinds', type: 'array', items: new OA\Items(ref: '#/components/schemas/Kind')),
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/ItemWithRelations')),
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
        $limit = $request->query('limit', null);
        $offset = $request->query('offset', 0);

        $result = Book::query()
            ->where('user_id', Auth::id())
            ->when($limit, function ($query) use ($offset, $limit) {
                return $query->offset((int)$offset)->limit((int)$limit);
            })
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
            'name' => $reqAll['name'],
            'user_id' => Auth::id(),
        ];
        $reqKinds = $reqAll['kinds'] ?? [];
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
        $this->assertBookBelongsToUser($book);

        $book->load(['kinds', 'items.images', 'items.kind']);
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
        $this->assertBookBelongsToUser($book);
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
                $reqKind['book_id'] = $book->id;
                Kind::updateOrCreate(
                    ['id' => $reqKind['id'] ?? null],
                    $reqKind
                );
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
        $this->assertBookBelongsToUser($book);

        $res = DB::transaction(function () use ($book) {
            // 削除対象の図鑑IDが使用中かチェック
            if ($book->items()->exists()) {
                throw new DataException('削除対象の図鑑が使用中です');
            }
            // 削除対象の種類IDが使用中かチェック
            $dbKindsIdList = $book->kinds()->pluck('id');
            if (Item::whereIn('kind_id', $dbKindsIdList)->exists()) {
                throw new DataException('削除対象の種類が使用中です');
            }
            $book->kinds()->delete();
            $book->delete();
            return $book;
        });
        return $this->customDestroyResponse($res);
    }

    /**
     * サムネイル画像登録API
     */
    #[OA\Post(
        path: '/books/{id}/thumbnail',
        operationId: 'bookUpdateThumbnail',
        tags: ['books'],
        summary: '図鑑サムネイル登録API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '図鑑ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'thumbnail', type: 'string', format: 'binary', description: 'サムネイル画像ファイル')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'file_name', type: 'string', description: 'サムネイル画像ファイル名', example: 'd2f3c4e5-6a7b-8c9d-0e1f-2g3h4i5j6k7l.jpg')
                    ]
                )
            )
        ]
    )]
    public function updateThumbnail(BookUpsertThumbnailRequest $request, Book $book)
    {
        $this->assertBookBelongsToUser($book);

        // 画像ファイルのバリデーション
        $request->validated();

        $thumbnail = $request->file('thumbnail');

        // 画像ファイル名作成
        $extension = $this->getImageExtension($thumbnail);
        $fileName = Str::uuid() . '.' . $extension;

        // 現在のサムネールファイル名取得
        $oldThumbnail = $book->thumbnail;

        // DB更新
        $book->thumbnail = $fileName;
        $book->save();

        // 画像ファイルアップロード（圧縮処理を追加）
        $compressedImage = $this->processAndCompressImage($thumbnail);
        Storage::disk('s3')->put('thumbnails/' . $fileName, $compressedImage);

        // 古いサムネイルファイル削除
        if ($oldThumbnail) {
            Storage::disk('s3')->delete('thumbnails/' . $oldThumbnail);
        }

        return response()->json(['file_name' => $fileName]);
    }

    /**
     * Bookのuser_idがログインユーザIDかを確認し、違う場合は403エラー
     */
    protected function assertBookBelongsToUser(Book $book)
    {
        if ($book->user_id !== Auth::id()) {
            abort(403, '権限がありません');
        }
    }
}