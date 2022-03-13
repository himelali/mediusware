<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $title = $request->get('title');
        $variant = $request->get('variant');
        $min_price = $request->get('price_from');
        $max_price = $request->get('price_to');
        $date = $request->get('date');
        $variants = ProductVariant::select('title', 'variant as name')
            ->join('variants as v', 'v.id', '=', 'product_variants.variant_id')
            ->groupBy('variant', 'title')
            ->orderBy('title', 'asc')->get();
        $variants = $variants->groupBy('title')->all();
        $products = Product::when($title, function ($sql) use ($title) {
                $sql->where('title', 'like', '%'.$title.'%');
            })
            ->when($variant, function ($sql) use ($variant) {
                $sql->whereExists(function ($query) use ($variant) {
                    $query->select(DB::raw(1))
                        ->from('product_variant_prices as p')
                        ->where(function ($a) use ($variant) {
                            $a->whereExists(function ($q) use ($variant) {
                                $q->select(DB::raw(1))
                                    ->from('product_variants as v1')
                                    ->where('v1.variant', '=', $variant)
                                    ->whereColumn('p.product_variant_one', 'v1.id');
                            })->orWhereExists(function ($q) use ($variant) {
                                $q->select(DB::raw(1))
                                    ->from('product_variants as v2')
                                    ->where('v2.variant', '=', $variant)
                                    ->whereColumn('p.product_variant_two', 'v2.id');
                            })->orWhereExists(function ($q) use ($variant) {
                                $q->select(DB::raw(1))
                                    ->from('product_variants as v3')
                                    ->where('v3.variant', '=', $variant)
                                    ->whereColumn('p.product_variant_three', 'v3.id');
                            });
                        })->whereColumn('p.product_id', 'products.id');
                });
            })
            ->when($min_price>0 && $max_price>0, function ($sql) use ($min_price, $max_price) {
                $sql->whereExists(function ($query) use ($min_price, $max_price) {
                    $query->select(DB::raw(1))
                        ->from('product_variant_prices as p')
                        ->whereBetween('p.price', [$min_price, $max_price])
                        ->whereColumn('p.product_id', 'products.id');
                });
            })
            ->when($date, function ($sql) use ($date) {
                $sql->whereDate('created_at', '=', Carbon::parse($date)->toDateString());
            })
            ->latest()->paginate(2);

        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
