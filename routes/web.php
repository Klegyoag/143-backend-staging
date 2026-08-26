<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['service'=>'143 API','version'=>'v1']));
