<?php

if (!function_exists('image_storage_disk')) {
    /**
     * 画像用のストレージディスク名を返す
     */
    function image_storage_disk(): string
    {
        return app()->environment('local') ? 'local_img' : 's3_img';
    }
}
