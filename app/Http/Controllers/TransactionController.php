<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('transactions');
    }
}
