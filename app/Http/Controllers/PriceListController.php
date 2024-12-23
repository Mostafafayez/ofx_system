<?php

// app/Http/Controllers/PriceListController.php
namespace App\Http\Controllers;

use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PriceListController extends Controller
{

    use AuthorizesRequests;


    public function __construct()
    {

        $this->authorize('access-sales');
    }




    // Store a new price list
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'pricelist_path' => 'required|file|mimes:pdf,xlsx,csv|max:2048',

        ]);

        $file = $request->file('pricelist_path');
        $filePath = $file->store('price_lists', 'public');


        $priceList = PriceList::create([
            'description' => $request->description,
            'pricelist_path' => $filePath,
        ]);

        return response()->json($priceList, 201);
    }


    public function getAll()
    {
        $priceLists = PriceList::all();

        return response()->json($priceLists);
    }

    // Delete a price list


    public function delete($id)
    {
        $priceList = PriceList::find($id);

        if (!$priceList) {
            return response()->json(['message' => 'Price List not found'], 404);
        }


        if ($priceList->pricelist_path && Storage::disk('public')->exists($priceList->pricelist_path)) {
            Storage::disk('public')->delete($priceList->pricelist_path);
        }


        $priceList->delete();

        return response()->json(['message' => 'Price List deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        $priceList = PriceList::find($id);

        if (!$priceList) {
            return response()->json(['message' => 'Price List not found'], 404);
        }


        $request->validate([
            'pricelist_path' => 'required|file|mimes:pdf,xlsx,csv|max:2048',
            'description' => 'required|string',

        ]);


        $file = $request->file('pricelist_path');


        Storage::disk('public')->delete($priceList->pricelist_path);


        $filePath = $file->store('price_lists', 'public');


        $priceList->update([
            'pricelist_path' => $filePath,
        ]);

        $priceList->update([
            'description' => $request->description,
        ]);

        return response()->json($priceList);
    }

}
