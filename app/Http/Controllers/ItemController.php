<?php

namespace App\Http\Controllers;

use App\Exceptions\DataException;
use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemStoreRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ItemController extends BaseController
{
    #[OA\Schema(
        schema: 'Item',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'book_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'ゴールデンレトリバー'),
            new OA\Property(property: 'kind_id', type: 'integer', example: 1),
            new OA\Property(property: 'explanation', type: 'string', example: '金色の毛並みが特徴的な犬種です。'),
        ]
    )]
    #[OA\Schema(
        schema: 'ItemWithoutId',
        type: 'object',
        properties: [
            new OA\Property(property: 'book_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'ゴールデンレトリバー'),
            new OA\Property(property: 'kind_id', type: 'integer', example: 1),
            new OA\Property(property: 'explanation', type: 'string', example: '金色の毛並みが特徴的な犬種です。'),
        ]
    )]
    #[OA\Schema(
        schema: 'Image',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'item_id', type: 'integer', example: 1),
            new OA\Property(property: 'file_name', type: 'string', example: 'e0f3d662-8d62-32c2-beb4-b8d7d91b1862.png'),
        ]
    )]
    #[OA\Schema(
        schema: 'ItemWithRelations',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'book_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'ゴールデンレトリバー'),
            new OA\Property(property: 'kind_id', type: 'integer', example: 1),
            new OA\Property(property: 'explanation', type: 'string', example: '金色の毛並みが特徴的な犬種です。'),
            new OA\Property(property: 'book', type: 'object', ref: '#/components/schemas/Book'),
            new OA\Property(property: 'kind', type: 'object', ref: '#/components/schemas/Kind'),
            new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/Image')),
        ]
    )]  
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/items',
        operationId: 'itemIndex',
        tags: ['items'],
        summary: 'アイテム一覧取得API',
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
                        ref: '#/components/schemas/ItemWithRelations'
                    )
                )
            )
        ]
    )]
    public function index(ItemIndexRequest $request)
    {
        // GETパラメータのlimitとoffsetを取得
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 0);

        $result = Item::offset((int)$offset)
            ->limit((int)$limit)
            ->with('book')
            ->with('kind')
            ->with('images')
            ->get();

        return $this->customIndexResponse($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/items',
        operationId: 'itemStore',
        tags: ['items'],
        summary: 'アイテム登録API',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ItemWithoutId'
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Item'
                )
            )
        ]
    )]
    public function store(ItemStoreRequest $request)
    {
        $result = Item::create($request->validated());

        return $this->customStoreResponse($result);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/items/{id}',
        operationId: 'itemShow',
        tags: ['items'],
        summary: 'アイテム詳細取得API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'アイテムID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ItemWithRelations'
                )
            )
        ]
    )]
    public function show(Item $item)
    {
        $item->load(['book', 'kind', 'images']);
        return $this->customShowResponse($item);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/items/{id}',
        operationId: 'itemUpdate',
        tags: ['items'],
        summary: 'アイテム更新API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'アイテムID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ItemWithoutId'
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Item'
                )
            )
        ]
    )]
    public function update(ItemUpdateRequest $request, Item $item)
    {
        $item->update($request->validated());

        return $this->customUpdateResponse($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/items/{id}',
        operationId: 'itemDestroy',
        tags: ['items'],
        summary: 'アイテム削除API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'アイテムID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Item'
                )
            )
        ]
    )]
    public function destroy(Item $item)
    {
        $res = DB::transaction(function () use ($item) {
            // 削除対象のアイテムIDに画像が残っているかチェック
            if ($item->images()->exists()) {
                throw new DataException('削除対象のアイテムに画像がまだあります');
            }
            $item->delete();
            return $item;
        });
        return $this->customDestroyResponse($res);
    }
}
