<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('components.layouts.app')] class extends Component {
    
    public function processPayment($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => 'paid']); // Status "paid" akan diteruskan ke Dapur otomatis
        \Flux::toast('Pembayaran Lunas! Pesanan terkirim ke Dapur.');
    }

    public function cancelOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => 'cancelled']);
        if ($order->table) {
            $order->table->update(['status' => 'available']); // release table kalau batal
        }
        \Flux::toast('Order ditolak/dibatalkan.');
    }

    public function finishTable($orderId)
    {
        // Tombol opsional untuk membebaskan meja jika tamu sudah pulang 
        $order = Order::findOrFail($orderId);
        if ($order->table) {
            $order->table->update(['status' => 'available']);
        }
        \Flux::toast('Meja dibebaskan.');
    }

    public function with(): array
    {
        return [
            // Order yang menunggu pembayaran di Kasir
            'pendingOrders' => Order::with(['table', 'customer', 'orderItems.menu'])
                                ->where('status', 'pending')
                                ->orderBy('created_at', 'asc')->get(),
                                
            // Riwayat status order hari ini
            'historyOrders' => Order::with(['table', 'customer'])
                                ->whereIn('status', ['paid', 'completed', 'cancelled'])
                                ->whereDate('created_at', today())
                                ->orderBy('updated_at', 'desc')->take(20)->get(),
        ];
    }
};
?>

<div class="w-full flex flex-col gap-6 h-full" wire:poll.10s>
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <flux:heading size="xl" level="1">Cashier Dashboard 💰</flux:heading>
            <flux:subheading>Terima pembayaran lunas terlebih dahulu sebelum pesanan masuk ke Dapur</flux:subheading>
        </div>
        
        <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-3 py-1.5 rounded-full text-sm font-bold animate-pulse">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            POS Sinkronisasi
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        
        <!-- KOLOM KIRI: MENUNGGU PEMBAYARAN -->
        <div class="flex flex-col gap-4">
            <div class="bg-orange-100 dark:bg-orange-950/30 border-l-4 border-orange-500 p-3 rounded-r-lg font-bold text-orange-700 dark:text-orange-400 flex justify-between items-center shadow-sm">
                <span>Pesanan Menunggu Pembayaran</span>
                <span class="bg-orange-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">{{ $pendingOrders->count() }}</span>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
                @forelse($pendingOrders as $order)
                    <flux:card class="border-orange-200 dark:border-orange-900 shadow-sm relative overflow-hidden bg-white dark:bg-zinc-800">
                        <div class="absolute top-0 right-0 p-2 text-xs font-bold bg-orange-100 dark:bg-orange-900 text-orange-700 rounded-bl-xl">Belum Bayar</div>
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <flux:badge color="zinc" size="sm" class="mb-1">{{ $order->table->name ?? 'Tanpa Meja' }}</flux:badge>
                                <h3 class="font-bold text-lg">{{ $order->customer->name ?? 'Guest' }}</h3>
                                <div class="text-xs text-zinc-500 mt-1">Dipesan: {{ $order->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-4 border-y border-zinc-100 dark:border-zinc-700 py-4">
                            @foreach($order->orderItems as $item)
                                <div class="flex justify-between text-sm">
                                    <div class="font-medium">{{ $item->quantity }}x {{ $item->menu->name ?? 'Menu Dihapus' }}</div>
                                    <div class="text-zinc-500">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                                </div>
                                @if($item->notes)
                                    <div class="text-xs text-red-500 pl-4 block mt-0.5"><flux:icon.exclamation-circle class="size-3 inline" /> {{ $item->notes }}</div>
                                @endif
                            @endforeach
                            
                            @if($order->order_notes)
                                <div class="text-xs bg-zinc-100 dark:bg-zinc-800 p-2 rounded block mt-2">
                                    <strong>Catatan Meja:</strong> {{ $order->order_notes }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/30 p-4 rounded-xl flex justify-between items-center mb-6">
                            <span class="text-sm font-bold uppercase text-indigo-700 dark:text-indigo-400">Total Tagihan</span>
                            <span class="text-2xl font-black text-indigo-700 dark:text-indigo-400">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="flex gap-2">
                            <flux:button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Yakin membatalkan pesanan ini?" variant="danger" icon="x-mark"></flux:button>
                            <flux:button wire:click="processPayment({{ $order->id }})" variant="primary" class="w-full border-0 !bg-emerald-600 hover:!bg-emerald-700 font-bold p-5">
                                <flux:icon.banknotes class="size-5 mr-2" /> TERIMA PEMBAYARAN LUNAS
                            </flux:button>
                        </div>
                    </flux:card>
                @empty
                    <div class="text-center p-12 bg-zinc-50 dark:bg-zinc-800/30 rounded-xl border border-dashed text-zinc-400">
                        <flux:icon.banknotes class="size-12 mx-auto mb-2 opacity-20" />
                        Belum ada pesanan yang menunggu pembayaran.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- KOLOM KANAN: RIWAYAT HARI INI -->
        <div class="flex flex-col gap-4">
            <div class="bg-zinc-100 dark:bg-zinc-800 border-l-4 border-zinc-500 p-3 rounded-r-lg font-bold text-zinc-700 dark:text-zinc-300 flex justify-between items-center shadow-sm">
                <span>Riwayat Transaksi (Per 20 Terakhir)</span>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden divide-y divide-zinc-100 dark:divide-zinc-700 shadow-sm">
                @forelse($historyOrders as $order)
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-sm">{{ $order->customer->name ?? 'Guest' }} <span class="text-zinc-400 font-normal">({{ $order->table->name ?? '-' }})</span></h4>
                            <span class="text-xs font-bold text-zinc-500">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span> | <span class="text-xs text-zinc-400">{{ $order->updated_at->format('H:i') }}</span>
                            
                            @if($order->status === 'completed' && $order->table && $order->table->status === 'occupied')
                                <div class="mt-2">
                                    <button wire:click="finishTable({{ $order->id }})" class="text-[10px] bg-zinc-200 dark:bg-zinc-700 px-2 py-1 rounded hover:bg-zinc-300 transition">Bebaskan Meja</button>
                                </div>
                            @endif
                        </div>
                        
                        <flux:badge color="{{ match($order->status) { 'cancelled' => 'red', 'paid' => 'blue', 'completed' => 'green', default => 'zinc' } }}" size="sm">
                            @if($order->status == 'paid') Sedang Dimasak @else {{ ucfirst($order->status) }} @endif
                        </flux:badge>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-zinc-400">Belum ada transaksi hari ini.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
