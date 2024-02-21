<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventoryController extends Controller
{
    public function index(){
        $response = Http::get('https://api.npoint.io/dd85ed11b9d8646c5709');
        
        if ($response->failed()) {
            return response()->json(['message' => 'Failed to fetch inventory data' , 'data'=>[] , 'status'=>false , 'code'=>400], 400);
        }

        $data = $response->json();

        $inventories = collect($data['hotels']);

        if (request()->has('hotel_name')) {
            $hotel_name = request('hotel_name');
            $inventories = $inventories->filter(function ($item) use ($hotel_name) {
                // Search in hotel name or city
                return stripos($item['name'], $hotel_name) !== false;
            });
        }

        if (request()->has('city_name')) {
            $city_name = request('city_name');
            $inventories = $inventories->filter(function ($item) use ($city_name) {
                return stripos($item['city'], $city_name) !== false;
            });
        }

        if (request()->has('price_from')) {
            $price_from = request('price_from');
            $inventories = $inventories->filter(function ($item) use ($price_from) {
                return $item['price'] >= $price_from;
            });
        }

        if (request()->has('price_to')) {
            $price_to = request('price_to');
            $inventories = $inventories->filter(function ($item) use ($price_to) {
                return $item['price'] <= $price_to;
            });
        }

        if (request()->has('start_date') || request()->has('end_date')) {
            $startDate = strtotime(request('start_date') ?? '1900-01-01');
            $endDate = strtotime(request('end_date') ?? '2100-12-31');

            $inventories = $inventories->filter(function ($item) use ($startDate, $endDate) {
                foreach ($item['availability'] as $key=>$availability) {
                    $availStartDate = strtotime(date('Y-m-d', strtotime($availability['from'])));
                    $availEndDate = strtotime(date('Y-m-d', strtotime($availability['to'])));
                    if ($availEndDate <= $endDate && $availStartDate >= $startDate) {
                        return true;
                    }

                }
                return false;
            });
        }
        

        return response()->json(['message' => 'inventories' , 'data'=>$inventories->values() , 'status'=>true , 'code'=>200], 200);
    }
}
