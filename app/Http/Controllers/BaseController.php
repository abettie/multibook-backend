<?php

namespace App\Http\Controllers;

use App\Exceptions\DataException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;

#[OA\Info(
    title: 'マルチ図鑑API',
    version: '0.1.0',
    description: 'マルチ図鑑APIの仕様書です。'
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: '開発環境'
)]
class BaseController extends Controller
{
    protected function customIndexResponse (Collection $body)
    {
        // APIレスポンスの共通形式はここで定義
        return response()->json($body, 200);
    }

    protected function customStoreResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customShowResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customUpdateResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customDestroyResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customErrorResponse (string $message)
    {
        return response()->json(['message' => $message], 400);
    }

    /**
     * 画像をリサイズ・圧縮し、制限容量以内に収める
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return string バイナリデータ
     */
    protected function processAndCompressImage($uploadedFile)
    {
        $maxSize = 1024 * 1024 * 2; // 2MB
        $maxWidth = 1000; // px
        $maxHeight = 1000; // px

        // Intervention Imageで画像を読み込み
        $manager = new ImageManager(new Driver());
        $image = $manager->read($uploadedFile->getPathname());

        // サイズ制限以内にリサイズ（縦横比維持）
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

        // 制限容量を超えている場合は品質を下げて再圧縮
        while (strlen($data) > $maxSize && $quality > 10) {
            $quality -= 10;
            $data = (string) $image->encode(new AutoEncoder(quality: $quality));
        }

        // それでも制限容量を超えていたら、さらに5%ずつ下げる
        while (strlen($data) > $maxSize && $quality > 5) {
            $quality -= 5;
            $data = (string) $image->encode(new AutoEncoder(quality: $quality));
        }

        // 最終的に制限容量を超えていたら例外
        if (strlen($data) > $maxSize) {
            throw new DataException('画像容量を制限容量以下にできませんでした');
        }

        return $data;
    }

    /**
     * 画像のmimeタイプから拡張子を取得
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @return string 拡張子
     */
    protected function getImageExtension($uploadedFile)
    {
        $mimeType = $uploadedFile->getClientMimeType();
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg', // デフォルトはjpg
        };
        return $extension;
    }
}
