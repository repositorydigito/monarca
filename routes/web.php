<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login', 301);
