<?php

namespace App\Http\Controllers;

use App\Exceptions\DataException;
use App\Http\Requests\ImageIndexRequest;
use App\Http\Requests\ImageStoreRequest;
use App\Http\Requests\ImageUpdateRequest;
use App\Models\Image;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;

class ImageController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/images',
        operationId: 'imageIndex',
        tags: ['images'],
        summary: '画像一覧取得API',
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
                        ref: '#/components/schemas/Image'
                    )
                )
            )
        ]
    )]
    public function index(ImageIndexRequest $request)
    {
        // GETパラメータのlimitとoffsetを取得
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 0);

        $result = Image::offset((int)$offset)
            ->limit((int)$limit)
            ->get();

        return $this->customIndexResponse($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/images',
        operationId: 'imageStore',
        tags: ['images'],
        summary: '画像登録API',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer', description: 'アイテムID', example: 1),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: '画像ファイル')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Image'
                )
            )
        ]
    )]
    public function store(ImageStoreRequest $request)
    {
        // 画像ファイル名作成
        $extension = $request->file('image')->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;

        $reqAll = $request->validated();
        // 該当のitem_idのデータが存在するか
        if (!Item::where('id', $reqAll['item_id'])->exists()) {
            throw new DataException('item_idが存在しません');
        }
        $dbParams = [
            'item_id' => $reqAll['item_id'],
            'file_name' => $fileName
        ];
        $result = Image::create($dbParams);

        try {
            // 画像をリサイズ・圧縮してS3にアップロード
            $imageData = $this->processAndCompressImage($request->file('image'));
            Storage::disk('s3')->put('images/' . $fileName, $imageData);
        } catch (\Exception $e) {
            // エラーが発生した場合、DBからレコードを削除
            $result->delete();
            throw $e;
        }

        return $this->customStoreResponse($result);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/images/{id}',
        operationId: 'imageShow',
        tags: ['images'],
        summary: '画像取得API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '画像ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Image'
                )
            )
        ]
    )]
    public function show(Image $image)
    {
        return $this->customShowResponse($image);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Post(
        path: '/updateImages/{id}',
        operationId: 'imageUpdate',
        tags: ['images'],
        summary: '画像更新API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '画像ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'item_id', type: 'integer', description: 'アイテムID', example: 1),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: '画像ファイル')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Image'
                )
            )
        ]
    )]
    public function update(ImageUpdateRequest $request, Image $image)
    {
        $reqAll = $request->validated();
        if ((int)$reqAll['item_id'] !== (int)$image->item_id) {
            // item_idが変更されることは仕様上あり得ないため、エラーとする
            throw new DataException('item_idは変更できません');
        }

        // 画像ファイル名作成
        $extension = $request->file('image')->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $oldFileName = $image->file_name;

        // DB更新
        $image->update(['file_name' => $fileName]);

        // 画像ファイル更新（削除→アップロード）
        Storage::disk('s3')->delete('images/' . $oldFileName);

        // 画像をリサイズ・圧縮してS3にアップロード
        $imageData = $this->processAndCompressImage($request->file('image'));
        Storage::disk('s3')->put('images/' . $fileName, $imageData);

        return $this->customUpdateResponse($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/images/{id}',
        operationId: 'imageDestroy',
        tags: ['images'],
        summary: '画像削除API',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '画像ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Image'
                )
            )
        ]
    )]
    public function destroy(Image $image)
    {
        if(Storage::disk('s3')->exists('images/' . $image->file_name)) {
            // 画像ファイル削除
            Storage::disk('s3')->delete('images/' . $image->file_name);
        } else {
            logger()->warning('画像ファイルが存在しません', ['file_name' => $image->file_name]);
        }

        // DB削除
        $image->delete();

        return $this->customDestroyResponse($image);
    }

    /**
     * 画像をリサイズ・圧縮し、1MB以内に収める
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return string バイナリデータ
     */
    protected function processAndCompressImage($uploadedFile)
    {
        $maxSize = 1024 * 1024; // 1MB
        $maxWidth = 1000;
        $maxHeight = 1000;

        // Intervention Imageで画像を読み込み
        $manager = new ImageManager(new Driver());
        $image = $manager->read($uploadedFile->getPathname());

        // 1000x1000px以内にリサイズ（縦横比維持）
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image = $image->scale($maxWidth, $maxHeight);
        }

        // 画像を一時的に圧縮してバッファに保存
        $quality = 90;
        $format = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($format, ['jpg', 'jpeg', 'png', 'webp'])) {
            $format = 'jpg';
        }

        $data = (string) $image->encode(new AutoEncoder(quality: $quality));

        // 1MBを超えている場合は品質を下げて再圧縮
        while (strlen($data) > $maxSize && $quality > 10) {
            $quality -= 10;
            $data = (string) $image->encode(new AutoEncoder(quality: $quality));
        }

        // それでも1MBを超えていたら、さらに5%ずつ下げる
        while (strlen($data) > $maxSize && $quality > 5) {
            $quality -= 5;
            $data = (string) $image->encode(new AutoEncoder(quality: $quality));
        }

        // 最終的に1MBを超えていたら例外
        if (strlen($data) > $maxSize) {
            throw new DataException('画像サイズを1MB以下にできませんでした');
        }

        return $data;
    }
}
