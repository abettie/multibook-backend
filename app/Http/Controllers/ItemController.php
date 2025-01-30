<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemStoreRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
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
    public function store(ItemStoreRequest $request)
    {
        $result = Item::create($request->validated());

        return $this->customStoreResponse($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        $item->load(['book', 'kind', 'images']);
        return $this->customShowResponse($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemUpdateRequest $request, Item $item)
    {
        $item->update($request->validated());

        return $this->customUpdateResponse($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return $this->customDestroyResponse($item);
    }
}
