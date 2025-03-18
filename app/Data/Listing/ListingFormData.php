<?php

namespace App\Data\Listing;

use App\Data\Item\ItemData;
use App\Enums\ListingType;
use App\Models\Listing;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;

class ListingFormData extends Data
{
    public function __construct(
        public ?int $id,
        public ListingType $type,
        public string $price,
        public ?int $quantity,
        public ?string $notes,
        public string $username,
        public ?ItemData $item,
        public ?array $usernames = []
    ) {}

    public static function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'username' => [
                'required',
                'string',
                'max:12',
                'regex:/^(?! )[A-Za-z0-9 _]+(?<! )$/',
                function ($attribute, $value, $fail) {
                    if (Auth::user()->is_admin) {
                        return;
                    }
                    
                    if (!in_array($value, Auth::user()->usernames->pluck('username')->toArray())) {
                        $fail('The selected username is invalid.');
                    }
                },
            ],
            'notes' => ['nullable', 'string'],
            'item.id' => ['required', 'integer', 'exists:items,id'],
            'type' => [
                'required',
                'string',
                'in:buy,sell',
                function ($attribute, $value, $fail) {
                    if (Auth::user()->is_admin) {
                        return;
                    }

                    $existingListings = Listing::active()
                        ->where('user_id', Auth::id())
                        ->where('type', $value)
                        ->where('id', '!=', request('id'))
                        ->count();

                    if ($existingListings >= 8) {
                        $fail('You cannot have more than eight active listings of the same type.');
                    }
                },
                function ($attribute, $value, $fail) {
                    if (Auth::user()->is_admin) {
                        return;
                    }

                    $existingListing = Listing::active()->where('type', $value)
                        ->where('user_id', Auth::id())
                        ->where('item_id', request('item.id'))
                        ->where('id', '!=', request('id'))
                        ->first();

                    if ($existingListing) {
                        $fail('You cannot have multiple listings of the same item and type. Please update the existing listing instead.');
                    }
                },
            ],
        ];
    }

    public function getListingData(): array
    {
        return $this->except('item', 'id', 'usernames')->toArray();
    }
}
