<?php

namespace App\Http\Requests;

use App\Enums\OrderSideEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trading_pair_id' => ['required', 'exists:trading_pairs,id'],
            'side' => ['required', Rule::enum(OrderSideEnum::class)],
            'price' => ['required', 'numeric', 'min:0.00000001'],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'client_order_id' => ['required', 'uuid', 'unique:orders,client_order_id'],
        ];
    }
}
