<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Session;

new #[Layout('components.layouts.guest')] class extends Component {
    public Table $table;
    
    public string $step = 'welcome';
    
    public string $name = '';
    public string $phone = '';

    public array $cart = [];
    public string $orderNotes = '';
    
    public ?Order $createdOrder = null;

    public function mount($token)
    {
        $this->table = Table::where('qr_code_token', $token)->firstOrFail();
        
        if (Session::has('customer_id')) {
            $this->step = 'menu';
            $customer = Customer::find(Session::get('customer_id'));
            if($customer) {
                $this->name = $customer->name;
                $this->phone = $customer->phone;
            }
        }
    }

    public function startOrder()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $customer = Customer::firstOrCreate(
            ['phone' => $this->phone],
            ['name' => $this->name]
        );

        Session::put('customer_id', $customer->id);
        $this->step = 'menu';
    }

    public function addToCart($menuId)
    {
        $menu = Menu::findOrFail($menuId);
        
        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['qty']++;
        } else {
            $this->cart[$menuId] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'qty' => 1,
                'notes' => '',
                'image' => $menu->image
            ];
        }
    }

    public function removeFromCart($menuId)
    {
        if (isset($this->cart[$menuId])) {
            if ($this->cart[$menuId]['qty'] > 1) {
                $this->cart[$menuId]['qty']--;
            } else {
                unset($this->cart[$menuId]);
            }
        }
    }

    public function updateNote($menuId, $note)
    {
        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['notes'] = $note;
        }
    }

    public function goToCart()
    {
        if (count($this->cart) > 0) {
            $this->step = 'cart';
        }
    }

    public function backToMenu()
    {
        $this->step = 'menu';
    }

    public function checkout()
    {
        if (empty($this->cart)) return;

        $customerId = Session::get('customer_id');
        if (!$customerId) {
            $this->step = 'welcome';
            return;
        }

        $totalPrice = collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);

        $this->table->update(['status' => 'occupied']);

        $order = Order::create([
            'table_id' => $this->table->id,
            'customer_id' => $customerId,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'order_notes' => $this->orderNotes,
        ]);

        foreach ($this->cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_id' => $item['id'],
                'quantity' => $item['qty'],
                'unit_price' => $item['price'],
                'subtotal' => $item['price'] * $item['qty'],
                'notes' => $item['notes'],
            ]);
        }

        $this->cart = [];
        $this->orderNotes = '';
        $this->createdOrder = $order;
        $this->step = 'success';
    }

    public function pollOrderStatus()
    {
        if ($this->createdOrder) {
            $this->createdOrder->refresh();
        }
    }

    public function with(): array
    {
        $categories = [];
        if ($this->step == 'menu') {
            $categories = \App\Models\Category::with(['menus' => function($q) {
                $q->where('is_available', true);
            }])->get();
        }

        return [
            'categories' => $categories,
            'cartCount' => collect($this->cart)->sum('qty'),
            'cartTotal' => collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty'])
        ];
    }
};
?>

<div class="w-full flex flex-col min-h-screen relative" x-data="{
    // local states non-important for php
}">
    <!-- Header Area -->
    <div class="sticky top-0 z-10 bg-white/90 dark:bg-zinc-800/90 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-700 p-4 shrink-0 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight">POS Café</h1>
            <p class="text-xs text-zinc-500 font-medium">{{ $table->name }}</p>
        </div>
        
        @if($step == 'menu' && $cartCount > 0)
            <button wire:click="goToCart" class="relative bg-zinc-100 dark:bg-zinc-700 p-2 rounded-full hover:bg-zinc-200 transition">
                <flux:icon.shopping-bag class="size-6 text-zinc-700 dark:text-zinc-200" />
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $cartCount }}</span>
            </button>
        @endif
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto p-4 pb-24">
        
        {{-- STEP 1: WELCOME FORM --}}
        @if($step == 'welcome')
            <div class="flex flex-col h-full justify-center">
                <div class="text-center mb-8">
                    <div class="mx-auto w-16 h-16 bg-zinc-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mb-4">
                        <flux:icon.user class="size-8 text-zinc-500" />
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Selamat Datang!</h2>
                    <p class="text-zinc-500 text-sm">Silakan isi data diri Anda sebelum memesan makanan di {{ $table->name }}.</p>
                </div>

                <form wire:submit="startOrder" class="space-y-4">
                    <flux:input wire:model="name" label="Nama Anda" placeholder="Cth: Budi" required />
                    <flux:input wire:model="phone" type="tel" label="Nomor WhatsApp" placeholder="Cth: 08123456789" required />
                    
                    <flux:button type="submit" variant="primary" class="w-full mt-4 !rounded-xl !py-3">Mulai Pesan</flux:button>
                </form>
            </div>
        @endif

        {{-- STEP 2: MENU LIST --}}
        @if($step == 'menu')
            <div class="space-y-8">
                @foreach($categories as $category)
                    @if($category->menus->count() > 0)
                        <div>
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <span class="w-2 h-6 bg-indigo-500 rounded-full"></span>
                                {{ $category->name }}
                            </h3>
                            
                            <div class="space-y-4">
                                @foreach($category->menus as $menu)
                                    <div class="flex gap-4 p-3 bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 rounded-2xl shadow-sm">
                                        @if($menu->image)
                                            <img src="{{ Storage::url($menu->image) }}" class="w-24 h-24 object-cover rounded-xl" alt="{{ $menu->name }}">
                                        @else
                                            <div class="w-24 h-24 bg-zinc-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center">
                                                <flux:icon.photo class="size-8 text-zinc-300" />
                                            </div>
                                        @endif
                                        
                                        <div class="flex-1 flex flex-col justify-between">
                                            <div>
                                                <h4 class="font-bold text-base">{{ $menu->name }}</h4>
                                                @if($menu->description)
                                                    <p class="text-xs text-zinc-500 line-clamp-2 mt-1">{{ $menu->description }}</p>
                                                @endif
                                            </div>
                                            
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="font-bold text-indigo-600 dark:text-indigo-400">Rp {{ number_format($menu->price, 0, ',', '.') }}</span>
                                                
                                                @if(isset($cart[$menu->id]))
                                                    <div class="flex items-center gap-3 bg-zinc-100 dark:bg-zinc-700 rounded-full px-1 py-1">
                                                        <button wire:click="removeFromCart({{ $menu->id }})" class="w-6 h-6 rounded-full bg-white dark:bg-zinc-600 flex items-center justify-center shadow-sm">
                                                            <flux:icon.minus class="size-3" />
                                                        </button>
                                                        <span class="text-sm font-bold w-4 text-center">{{ $cart[$menu->id]['qty'] }}</span>
                                                        <button wire:click="addToCart({{ $menu->id }})" class="w-6 h-6 rounded-full bg-indigo-500 text-white flex items-center justify-center shadow-sm">
                                                            <flux:icon.plus class="size-3" />
                                                        </button>
                                                    </div>
                                                @else
                                                    <button wire:click="addToCart({{ $menu->id }})" class="bg-zinc-900 dark:bg-white dark:text-zinc-900 text-white text-xs font-bold px-4 py-2 rounded-full shadow-sm hover:scale-105 transition">
                                                        Tambah
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            
            @if($cartCount > 0)
                <div class="fixed bottom-0 left-0 right-0 p-4 max-w-md mx-auto bg-white/80 dark:bg-zinc-800/80 backdrop-blur border-t border-zinc-200 dark:border-zinc-700 z-50 rounded-t-2xl">
                    <button wire:click="goToCart" class="w-full bg-indigo-600 text-white rounded-2xl py-3.5 px-4 flex items-center justify-between shadow-xl shadow-indigo-200 dark:shadow-none hover:bg-indigo-700 transition">
                        <div class="flex flex-col text-left">
                            <span class="text-[10px] text-indigo-200 uppercase tracking-widest font-bold">Total Pesanan ({{ $cartCount }} Item)</span>
                            <span class="text-lg font-bold">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-2 font-bold text-sm bg-indigo-500/50 px-3 py-1.5 rounded-xl">
                            Selanjutnya <flux:icon.chevron-right class="size-4" />
                        </div>
                    </button>
                </div>
            @endif
        @endif

        {{-- STEP 3: CART/CHECKOUT --}}
        @if($step == 'cart')
            <div class="space-y-6">
                <button wire:click="backToMenu" class="flex items-center gap-2 text-sm font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">
                    <flux:icon.arrow-left class="size-4" /> Kembali ke Menu
                </button>

                <div>
                    <h2 class="text-2xl font-bold mb-4">Keranjang Anda</h2>
                    
                    <div class="space-y-4">
                        @foreach($cart as $id => $item)
                            <div class="flex gap-4 p-3 bg-white dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700 rounded-2xl">
                                @if($item['image'])
                                    <img src="{{ Storage::url($item['image']) }}" class="w-16 h-16 object-cover rounded-xl" alt="Menu">
                                @else
                                    <div class="w-16 h-16 bg-zinc-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center">
                                        <flux:icon.photo class="size-6 text-zinc-300" />
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-bold text-sm">{{ $item['name'] }}</h4>
                                        <span class="font-bold text-sm whitespace-nowrap ml-2">Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center gap-2 bg-zinc-100 dark:bg-zinc-700 rounded-full px-1 py-1">
                                            <button wire:click="removeFromCart({{ $id }})" class="w-5 h-5 rounded-full bg-white dark:bg-zinc-600 flex items-center justify-center shadow-sm">
                                                <flux:icon.minus class="size-3" />
                                            </button>
                                            <span class="text-xs font-bold w-4 text-center">{{ $item['qty'] }}</span>
                                            <button wire:click="addToCart({{ $id }})" class="w-5 h-5 rounded-full bg-indigo-500 text-white flex items-center justify-center shadow-sm">
                                                <flux:icon.plus class="size-3" />
                                            </button>
                                        </div>
                                        <div class="text-xs text-zinc-400">Rp {{ number_format($item['price'], 0, ',', '.') }}/porsi</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-4 rounded-2xl">
                    <flux:textarea wire:model="orderNotes" label="Catatan Tambahan (Opsional)" placeholder="Cth: Jangan pakai sambel, Es dipisah..." rows="2" />
                </div>

                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4 rounded-2xl space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">Subtotal</span>
                        <span class="font-medium">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">Pajak (0%)</span>
                        <span class="font-medium">Rp 0</span>
                    </div>
                    <div class="h-px bg-zinc-200 dark:bg-zinc-700 my-2"></div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total Pembayaran</span>
                        <span class="text-indigo-600 dark:text-indigo-400">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="fixed bottom-0 left-0 right-0 p-4 max-w-md mx-auto bg-white border-t z-50 pb-8">
                    <button wire:click="checkout" class="w-full bg-zinc-900 dark:bg-white dark:text-zinc-900 text-white rounded-2xl py-3.5 px-4 shadow-xl hover:scale-[1.02] active:scale-95 transition flex justify-center items-center gap-2 font-bold text-base">
                        Buat Pesanan Sekarang <flux:icon.check-circle class="size-5" />
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 4: SUCCESS POLLING --}}
        @if($step == 'success' && $createdOrder)
            <div class="flex flex-col h-full justify-center items-center text-center py-10" wire:poll.5s="pollOrderStatus">
                @if($createdOrder->status === 'completed')
                    <!-- STATUS: MASAKAN SELESAI -> PELANGGAN AMBIL / DIANTARKAN -->
                    <div class="mx-auto w-24 h-24 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-6">
                        <flux:icon.check-badge class="size-12" />
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Selesai! Pesanan Siap.</h2>
                    <p class="text-zinc-500 text-sm mb-8">Terima kasih banyak (Lunas). Anda bebas mengambil pesanan atau menunggu diantar oleh Pelayan kami.</p>
                
                @elseif($createdOrder->status === 'cancelled')
                    <!-- STATUS: DIBATALKAN OLEH KASIR -->
                    <div class="mx-auto w-24 h-24 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mb-6">
                        <flux:icon.x-circle class="size-12" />
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Pesanan Batal!</h2>
                    <p class="text-zinc-500 text-sm mb-8">Mohon maaf, meja/pesanan Anda ditolak Kasir. Segera hubungi Staf jika ada kesalahan.</p>

                @else
                    <!-- STATUS: PENDING (BELUM BAYAR), ATAU PAID (SEDANG DIMASAK) -->
                    <div class="mx-auto w-24 h-24 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center mb-6 animate-pulse">
                        <flux:icon.fire class="size-12" />
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Pesanan Anda Sedang Diproses</h2>
                    <p class="text-zinc-500 text-sm mb-8">Silakan selesaikan pembayaran lunas di meja **Kasir** terlebih dahulu jika belum bayar, lalu tunggu hidangan Anda Koki masak dengan penuh ketelitian.</p>
                @endif
                
                <div class="w-full bg-zinc-50 dark:bg-zinc-900 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-left mt-4">
                    <p class="text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2">Detail Pesanan #{{ str_pad($createdOrder->id, 5, '0', STR_PAD_LEFT) }}</p>
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-medium text-sm">Total Tagihan</span>
                        <span class="font-bold">Rp {{ number_format($createdOrder->total_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-sm">Status Sinkronisasi</span>
                        <flux:badge color="{{ match($createdOrder->status) { 'pending' => 'orange', 'paid' => 'blue', 'completed' => 'green', 'cancelled' => 'red', default => 'zinc' } }}" size="sm">
                            @if($createdOrder->status == 'pending') Menunggu Bayar @elseif($createdOrder->status == 'paid') Sedang Dimasak @else {{ ucfirst($createdOrder->status) }} @endif
                        </flux:badge>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
