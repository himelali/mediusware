@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" value="{{ request('title') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" class="form-control">
                        <option value="">All</option>
                        @foreach($variants as $key => $items)
                            <optgroup label="{{ $key }}">
                            @foreach($items as $item)
                                <option @if(request('variant') == $item->name) selected @endif value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{ request('price_from') }}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{ request('price_to') }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" value="{{ request('date') }}" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th style="width: 500px">Variant</th>
                        <th style="width: 150px">Action</th>
                    </tr>
                    </thead>
                    @if($products->count()>0)
                    <tbody>
                    @foreach($products as $i => $product)
                    <tr>
                        <td>{{ $products->perPage() * ($products->currentPage() - 1) + (++$i) }}</td>
                        <td>
                            {{ $product->title }}<br>
                            Created: {{ $product->created_at->diffForHumans() }}
                        </td>
                        <td>{!! $product->description !!}</td>
                        <td>
                            <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant-{{ $i }}">
                            @foreach($product->variant_prices as $item)
                                <div class="col-md-12">
                                    <div class="row">
                                        <dt class="col-sm-4 pb-0">
                                            @php
                                                $variant = [];
                                                if($item->variant_one && $item->variant_one->variant)
                                                    $variant[] = $item->variant_one->variant;
                                                if($item->variant_two && $item->variant_two->variant)
                                                    $variant[] = $item->variant_two->variant;
                                                if($item->variant_three && $item->variant_three->variant)
                                                    $variant[] = $item->variant_three->variant;
                                            @endphp
                                            {{ implode(' / ', $variant) }}
                                        </dt>
                                        <dd class="col-sm-8">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4 pb-0">Price : {{ number_format($item->price, 2) }}</dt>
                                                <dd class="col-sm-8 pb-0">InStock : {{ number_format($item->stock, 2) }}</dd>
                                            </dl>
                                        </dd>
                                    </div>
                                </div>
                            @endforeach
                            </dl>
                            <button onclick="$('#variant-{{ $i }}').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    @else
                    <tfoot>
                    <tr>
                        <td colspan="5" class="text-center">Sorry! No product found...</td>
                    </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{($products->currentpage()-1)*$products->perPage()+1}} to {{$products->currentpage()*$products->perpage()}} out of {{ $products->total() }}</p>
                </div>
                <div class="col-md-2">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
