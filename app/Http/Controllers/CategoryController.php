<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function apiIndex(){
        $items = Category::whereNull('category_id')->orderBy('created_at', 'desc')->get();
        return CategoryResource::collection($items);
    }
}
