<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

Route::get('/reset-password', function () {
    return view('emails/password_reset');
});
