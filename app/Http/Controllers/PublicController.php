<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Category;
use App\Models\Scent;
use App\Models\Size;

class PublicController extends Controller
{

    /* PÁGINA DE INICIO */

    public function home()
    {
        $featuredProducts = Product::where('is_seasonal', true)
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->take(4)
            ->get();

        $categories = Category::where('is_active', true)
            ->with([
                'products' => function ($query) {
                    $query->where('is_active', true)
                        ->whereNotNull('image')
                        ->limit(4)
                        ->select('id', 'category_id', 'image');
                }
            ])
            ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'products' => $featuredProducts,
            'categories' => $categories,
        ]);
    }


    /* DETALLE DE PRODUCTO */

    public function productDetail($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['sizes', 'category'])
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->firstOrFail();

        $relatedProducts = Product::where('id', '!=', $product->id)
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->inRandomOrder()
            ->take(4)
            ->get();

        $scents = Scent::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sizes = Size::orderBy('name')
            ->get(['id', 'name', 'extra_price']);

        return Inertia::render('ProductDetail', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'scents' => $scents,
            'sizes' => $sizes,
        ]);
    }


    /* CATEGORÍAS */

    public function categorias()
    {
        $categories = Category::with('products')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Categorias', [
            'categories' => $categories
        ]);
    }


    /* CATÁLOGO */

    public function velas(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with('category');

        if ($request->filled('search')) {

            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");

            });

        }

        if ($request->filled('category')) {

            $slug = $request->input('category');

            $query->whereHas('category', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });

        }

        $products = $query->orderBy('sort', 'asc')->get();

        $categories = Category::where('is_active', true)
            ->orderBy('sort')
            ->get();

        return Inertia::render('Velas', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }


    /* CARRITO */

    public function cart()
    {
        return Inertia::render('Cart');
    }


    /* DASHBOARD */

    public function dashboard()
    {
        return Inertia::render('Dashboard');
    }

}