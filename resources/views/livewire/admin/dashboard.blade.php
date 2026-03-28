<?php

use Livewire\Volt\Component;
use App\Models\Order;
use App\Models\Table;
use App\Models\Menu;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        return [
            'totalOrders' => Order::whereDate('created_at', today())->count(),
            'revenueToday' => Order::whereDate('created_at', today())->where('status', 'paid')->sum('total_price'),
            'activeTables' => Table::where('status', 'occupied')->count(),
            'totalMenus' => Menu::count(),
        ];
    }
};
?>

<div class="w-full">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">Dashboard Admin</flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-sm font-medium">Pesanan Hari Ini</span>
                    <span class="text-3xl font-bold mt-2">{{ $totalOrders }}</span>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-sm font-medium">Pendapatan Hari Ini</span>
                    <span class="text-3xl font-bold mt-2 text-green-600">Rp {{ number_format($revenueToday, 0, ',', '.') }}</span>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-sm font-medium">Meja Terisi</span>
                    <span class="text-3xl font-bold mt-2 text-orange-600">{{ $activeTables }}</span>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex flex-col">
                    <span class="text-zinc-500 text-sm font-medium">Total Menu Aktif</span>
                    <span class="text-3xl font-bold mt-2">{{ $totalMenus }}</span>
                </div>
            </flux:card>
        </div>
    </div>
</div>
