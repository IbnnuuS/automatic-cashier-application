<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('components.layouts.app')] class extends Component {
    
    public function markAsCompleted($orderId)
    {
        Order::where('id', $orderId)->update(['status' => 'completed']);
        \Flux::toast('Masakan Selesai! Pesanan siap diambil/diantar.');
    }

    public function with(): array
    {
        return [
            // Dapur HANYA melihat pesanan LUNAS (Paid) dari Kasir
            'paidOrders' => Order::with(['table', 'customer', 'orderItems.menu'])
                                ->where('status', 'paid')
                                ->orderBy('updated_at', 'asc')->get(),
                                
            // Riwayat Masakan Selesai
            'completedOrders' => Order::with(['table', 'customer'])
                                ->where('status', 'completed')
                                ->whereDate('created_at', today())
                                ->orderBy('updated_at', 'desc')->take(15)->get(),
        ];
    }
};
?>

<div class="w-full flex flex-col gap-6 h-full" wire:poll.10s>
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <flux:heading size="xl" level="1">Kitchen Panel 🔥</flux:heading>
            <flux:subheading>Daftar lunas yang telah dikonfirmasi Kasir dan harus dimasak segera.</flux:subheading>
        </div>
        
        <div class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-3 py-1.5 rounded-full text-sm font-bold animate-pulse">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
            </span>
            Live Updates
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        
        <!-- KOLOM KIRI: DAFTAR MASAK LUNAS (PAID) -->
        <div class="flex flex-col gap-4">
            <div class="bg-orange-100 dark:bg-orange-950/30 border-l-4 border-orange-500 p-3 rounded-r-lg font-bold text-orange-700 dark:text-orange-400 flex justify-between items-center shadow-sm">
                <span>Antrean Masak (SUDAH LUNAS)</span>
                <span class="bg-orange-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">{{ $paidOrders->count() }}</span>
            </div>

            <div class="grid grid-cols-1 gap-4">
                @forelse($paidOrders as $order)
                    <flux:card class="border-orange-200 dark:border-orange-900 shadow-sm relative overflow-hidden bg-white dark:bg-zinc-800">
                        <div class="absolute top-0 right-0 p-2 text-xs font-bold bg-orange-100 dark:bg-orange-900 text-orange-700 rounded-bl-xl animate-pulse">Proses Masak...</div>
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <flux:badge color="zinc" size="sm" class="mb-1">{{ $order->table->name ?? 'Nomor Meja Hilang' }}</flux:badge>
                                <h3 class="font-bold text-lg">{{ $order->customer->name ?? 'Guest' }}</h3>
                                <span class="text-xs text-orange-600 font-medium">Masuk Kasir: {{ $order->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-6 border-y border-zinc-100 dark:border-zinc-700 py-4">
                            @foreach($order->orderItems as $item)
                                <div class="flex justify-between text-sm p-1">
                                    <div class="font-bold text-base text-zinc-900 dark:text-zinc-100">{{ $item->quantity }}x {{ $item->menu->name ?? 'Menu Dihapus' }}</div>
                                </div>
                                @if($item->notes)
                                    <div class="text-xs mb-1 ml-4"><strong class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 px-2 py-0.5 rounded-md inline-block">Catatan: {{ $item->notes }}</strong></div>
                                @endif
                            @endforeach
                            
                            @if($order->order_notes)
                                <div class="text-xs bg-red-50 dark:bg-red-900/20 text-red-600 p-2 rounded border border-red-100 border-dashed mt-2">
                                    <strong>Catatan Spesial Meja:</strong> {{ $order->order_notes }}
                                </div>
                            @endif
                        </div>
                        
                        <flux:button wire:click="markAsCompleted({{ $order->id }})" wire:confirm="Yakin semua makanan di nota ini sudah selesai dimasak?" variant="primary" class="w-full !bg-orange-600 hover:!bg-orange-700 border-0 p-5 font-bold text-lg">
                            <flux:icon.check-circle class="size-6 mr-2" /> MASAKAN SELESAI
                        </flux:button>
                    </flux:card>
                @empty
                    <div class="text-center p-12 bg-zinc-50 dark:bg-zinc-800/30 rounded-xl border border-dashed text-zinc-400">
                        Belum ada pesanan lunas yang dikirim kasir. Dapur Santai.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- KOLOM KANAN: COMPLETED (SELESAI HARI INI) -->
        <div class="flex flex-col gap-4">
            <div class="bg-zinc-100 dark:bg-zinc-800 border-l-4 border-zinc-500 p-3 rounded-r-lg font-bold text-zinc-700 dark:text-zinc-300 flex justify-between items-center shadow-sm">
                <span>Riwayat Selesai Hari Ini</span>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden divide-y divide-zinc-100 dark:divide-zinc-700 shadow-sm">
                @forelse($completedOrders as $order)
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-sm">{{ $order->customer->name ?? '-' }} <span class="text-zinc-400 font-normal">({{ $order->table->name ?? '-' }})</span></h4>
                                <span class="text-xs text-zinc-500">Selesai: {{ $order->updated_at->format('H:i') }}</span>
                            </div>
                            <flux:badge color="green" size="sm">Selesai</flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-zinc-400">Belum ada pesanan yang diselesaikan.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
