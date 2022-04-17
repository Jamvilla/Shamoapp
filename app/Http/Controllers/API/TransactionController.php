<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id'); 
       $limit = $request->input('limit');
       $status = $request->input('status');
    
       if($id)
       {
           $transcation =Transaction::with(['items.product'])->find($id);

           if($transcation)
           {
               return ResponseFormatter::success(
                   $transcation,
                   'Data Transaksi berhasil diambil'
               );
           } else
           {
               return ResponseFormatter::error(
                   null,
                   'Data Transaksi tidak ada',
                   404
               );
           }
       }
       $transcation = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

       if($status)
       {
           $transcation->where('status', $status);
           
       }
       return ResponseFormatter::success(
           $transcation->paginate($limit),
           'Data List transaksi berhasil diambil'
       );
    }
    public function checkout(Request $request){
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:products,id',
            'total_prize' => 'required',
            'shipping_prize' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED'
        ]);
        $transcation = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);
        foreach ($request->items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transcation->id,
                'quantity' =>$product['quantity']
            ]);
        }
        return ResponseFormatter::success($transcation->load('items.product'), 'Transaksi berhasil');
    }
}
