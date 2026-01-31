<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    // --- PÁGINA DE VELAS  ---
    public function velas(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with('category'); 

        // Barra de búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por Categoría 
        if ($request->filled('category')) {
            $slug = $request->input('category');
            $query->whereHas('category', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        }

        // Resultados paginados 
        $products = $query->orderBy('created_at', 'desc')
                          ->paginate(12)
                          ->withQueryString();

        // Categorías para la lista lateral
        $categories = Category::where('is_active', true)->get();

        return Inertia::render('Velas', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }
}