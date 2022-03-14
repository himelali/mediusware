<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        $product = null;
        DB::transaction(function () use ($request, &$product) {
            $product = Product::create($request->all());
            $now = now()->toDateTimeString();
            $variants = [];
            foreach ($request->post('product_variant') as $variant) {
                foreach ($variant['tags'] as $item) {
                    $variants[] = [
                        'variant' => $item,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }
            if(!empty($variants)) {
                ProductVariant::insert($variants);
                $variants = ProductVariant::where('product_id', '=', $product->id)
                    ->pluck('id', 'variant')
                    ->toArray();
                $prices = [];
                foreach ($request->post('product_variant_prices') as $item) {
                    $items = explode('/', rtrim($item['title'], '/'));
                    $row = [
                        'product_id' => $product->id,
                        'price' => $item['price'],
                        'stock' => $item['stock'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ];

                    $row['product_variant_one'] = isset($items[0]) ? ($variants[$items[0]] ?? null) : null;
                    $row['product_variant_two'] = isset($items[1]) ? ($variants[$items[1]] ?? null) : null;
                    $row['product_variant_three'] = isset($items[2]) ? ($variants[$items[2]] ?? null) : null;
                    $prices[] = $row;
                }
                if(!empty($prices)) {
                    ProductVariantPrice::insert($prices);
                }
            }
            if(!empty($request->post('product_image'))) {
                $files = [];
                foreach ($request->post('product_image') as $image) {
                    $image = str_replace('data:image/jpeg;base64,', '', $image);
                    $image = str_replace('data:image/jpg;base64,', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $imageName = Str::random(15).'.'.'jpg';
                    try {
                        File::put(storage_path(). '/app/public/' . $imageName, base64_decode($image));
                        $files[] = [
                            'product_id' => $product->id,
                            'file_path' => $imageName,
                        ];
                    } catch (\Exception $e) {}
                }
                if(!empty($files)) {
                    ProductImage::insert($files);
                }
            }
        });
        if($product) {
            return response()->json([
                'message' => $product->title.' has been created',
            ]);
        }
        return response()->json([
            'error' => 'Something went wrong!',
        ]);
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
