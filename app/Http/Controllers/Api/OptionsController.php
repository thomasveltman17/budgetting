<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class OptionsController extends Controller
{
    /**
     * Account and category names a shortcut can offer in a "Choose from List" step.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'accounts' => Account::orderBy('label')->pluck('label'),
            'categories' => Category::where('is_archived', false)->orderBy('sort_order')->pluck('name'),
        ]);
    }
}
