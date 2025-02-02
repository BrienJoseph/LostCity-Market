<?php

namespace App\Http\Controllers;

use App\Data\Item\ItemData;
use App\Data\Listing\ListingData;
use App\Pages\Items\ItemsShowPage;
use App\Models\Item;
use App\Data\Listing\ListingFormData;
use App\Enums\ListingType;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

class ItemController
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        if (!$search) {
            return response()->json([]);
        }

        $items = Item::where('name', 'LIKE', "%{$search}%")
            ->orderByRaw('CHAR_LENGTH(name)')
            ->take(5)
            ->get();

        return response()->json(ItemData::collect($items));
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(Item $item) 
    {
        $itemData = ItemData::from($item);

        $listings = $item->listings()
            ->whereNull('deleted_at')
            ->where('updated_at', '>=', now()->subDays(2))
            ->latest()
            ->paginate(1);

        return inertia('items/show/page', new ItemsShowPage(
            item: $itemData,
            listingForm: new ListingFormData(
                id: null,
                type: ListingType::Buy,
                price: '',
                quantity: null,
                notes: '',
                username: '',
                item: $itemData,
            ),
            listings: ListingData::collect($listings, PaginatedDataCollection::class),
        ));
    }

    public function edit(Item $item) {}

    public function update(Request $request, Item $item) {}

    public function destroy(Item $item) {}
}
