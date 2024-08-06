<?php

namespace App\Jobs\Servers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order; // Asegúrate de importar el modelo Order correctamente
use Illuminate\Support\Facades\Log;

class CancelWeeklyOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     * @return void
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = Order::find($this->orderId);

        if ($order && $order->billing_cycle == 'weekly') {
            // Cambiar el estado del pedido a cancelado
            $order->status = 'canceled';
            $order->save();

            // Registrar en el log para depuración
            Log::info("Pedido semanal cancelado automáticamente. ID del Pedido: {$this->orderId}");
        }
    }
}
