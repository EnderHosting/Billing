<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Helpers\ExtensionHelper;
use App\Http\Controllers\Controller;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return view('admin.orders.index');
    }

    public function show(Order $order)
    {
        $products = [];
        // Loop through products
        foreach ($order->products as $product) {
            $link = ExtensionHelper::getLink($product);
            $product->link = $link;
            $products[] = $product;
        }

        return view('admin.orders.show', compact('order', 'products'));
    }

    public function destroyProduct(Order $order, OrderProduct $product)
    {
        // Terminar el servidor solo si es necesario
        ExtensionHelper::terminateServer($product);
        $product->delete();

        return redirect()->route('admin.orders.show', $order)->with('success', 'Product deleted');
    }

    public function changeProduct(Order $order, OrderProduct $product, Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'expiry_date' => 'required|date',
        ]);

        $product->price = $request->input('price');
        $product->quantity = $request->input('quantity');
        $product->expiry_date = $request->input('expiry_date');
        $product->save();

        return redirect()->route('admin.orders.show', $order);
    }

    public function destroy(Order $order)
    {
        foreach ($order->products()->get() as $product) {
            ExtensionHelper::terminateServer($product);
        }
        $order->products()->delete();
        $order->invoices()->delete();
        $order->delete();

        return redirect()->route('admin.orders')->with('success', 'Order deleted');
    }

    public function suspend(Order $order)
    {
        foreach ($order->products()->get() as $product) {
            ExtensionHelper::suspendServer($product);
            $product->status = 'suspended';
            $product->save();
        }

        return redirect()->route('admin.orders.show', $order);
    }

    public function unsuspend(Order $order)
    {
        foreach ($order->products()->get() as $product) {
            ExtensionHelper::unsuspendServer($product);
            $product->status = 'paid';
            $product->save();
        }

        return redirect()->route('admin.orders.show', $order);
    }

    public function create(Order $order)
{
    foreach ($order->products()->get() as $product) {
        // Log para el inicio de la creación del servidor
        Log::info('Intentando crear un servidor para el producto', [
            'producto' => $product->id, 
            'ciclo' => $product->billing_cycle,
            'config' => $product->toArray(), // Muestra la configuración completa del producto
        ]);

        try {
            // Crear el servidor
            if (!ExtensionHelper::createServer($product)) {
                Log::error('Error al crear el servidor para el producto', [
                    'producto' => $product->id, 
                    'mensaje' => 'La creación del servidor falló',
                ]);
                continue; // Salta al siguiente producto si falla la creación
            }
            
            $product->status = 'paid';
            $product->save();

            // Log para éxito de creación del servidor
            Log::info('Servidor creado exitosamente para el producto', ['producto' => $product->id]);
        } catch (\Exception $e) {
            Log::error('Excepción al crear el servidor para el producto', [
                'producto' => $product->id, 
                'error' => $e->getMessage(),
            ]);
            continue; // Salta al siguiente producto si hay una excepción
        }

        // Si el ciclo de facturación es semanal, programar la terminación
        if ($product->billing_cycle === 'weekly') {
            Log::info('Producto semanal detectado, programando terminación', ['producto' => $product->id]);
            $product->status = 'cancelled'; // Marcar como cancelado después de la creación
            $product->save();

            // Programar la terminación del servidor después de una semana
            \App\Jobs\Servers\TerminateServer::dispatch($product)->delay(now()->addWeek());
        }
    }

    return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully');
}




    public function paid(Order $order)
    {   
        foreach ($order->products()->get() as $product) {
            ExtensionHelper::unsuspendServer($product);
            $product->status = 'paid';
            $product->save();
        }

        foreach ($order->invoices()->get() as $invoice) {
            $invoice->status = 'paid';
            $invoice->save();
        }

        return redirect()->route('admin.orders.show', $order);
    }
}
