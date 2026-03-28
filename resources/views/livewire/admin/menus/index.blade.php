<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Menu;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination, WithFileUploads;

    public $menuId = null;
    public $category_id = '';
    public $name = '';
    public $description = '';
    public $price = '';
    public $is_available = true;
    public $image; // new image
    public $existing_image; // old image path

    public function editMenu($id)
    {
        $menu = Menu::findOrFail($id);
        $this->menuId = $id;
        $this->category_id = $menu->category_id;
        $this->name = $menu->name;
        $this->description = $menu->description;
        $this->price = $menu->price;
        $this->is_available = $menu->is_available;
        $this->existing_image = $menu->image;
    }

    public function resetForm()
    {
        $this->reset(['menuId', 'category_id', 'name', 'description', 'price', 'is_available', 'image', 'existing_image']);
    }

    public function store()
    {
        $this->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
            'image' => 'nullable|image|max:2048', // 2MB Max
        ]);

        $imagePath = $this->existing_image;
        if ($this->image) {
            $imagePath = $this->image->store('menus', 'public');
        }

        Menu::updateOrCreate(
            ['id' => $this->menuId],
            [
                'category_id' => $this->category_id,
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
                'is_available' => $this->is_available,
                'image' => $imagePath,
            ]
        );

        $this->resetForm();
    }

    public function deleteMenu($id)
    {
        Menu::findOrFail($id)->delete();
    }

    public function toggleAvailability($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->update(['is_available' => !$menu->is_available]);
    }

    public function with(): array
    {
        return [
            'menus' => Menu::with('category')->latest()->paginate(10),
            'categories' => Category::all(),
        ];
    }
};
?>

<div class="w-full">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">Manajemen Daftar Menu</flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <flux:card class="h-fit lg:col-span-1">
                <flux:heading size="lg" class="mb-4">{{ $menuId ? 'Edit Menu' : 'Tambah Menu Baru' }}</flux:heading>
                <form wire:submit="store" class="space-y-4">
                    <flux:input wire:model="name" label="Nama Menu" required />
                    
                    <flux:select wire:model="category_id" label="Kategori" required>
                        <flux:select.option value="">-- Pilih Kategori --</flux:select.option>
                        @foreach($categories as $cat)
                            <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input type="number" wire:model="price" label="Harga (Rp)" required />
                    <flux:textarea wire:model="description" label="Deskripsi" rows="3" />
                    
                    <flux:checkbox wire:model="is_available" label="Tersedia (Bisa dipesan)" />

                    <div class="p-4 border rounded-lg dark:border-zinc-700">
                        <label class="block text-sm font-medium mb-2">Foto Menu</label>
                        @if ($existing_image && !$image)
                            <img src="{{ Storage::url($existing_image) }}" class="w-full h-32 object-cover rounded mb-2">
                        @endif
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-full h-32 object-cover rounded mb-2">
                        @endif
                        <input type="file" wire:model.live="image" class="text-sm">
                        @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex items-center gap-2 pt-2">
                        @if($menuId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Batal</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full">{{ $menuId ? 'Update Menu' : 'Simpan Menu' }}</flux:button>
                    </div>
                </form>
            </flux:card>

            <div class="lg:col-span-3">
                <flux:card>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Menu & Kategori</flux:table.column>
                            <flux:table.column>Harga</flux:table.column>
                            <flux:table.column>Foto</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($menus as $menu)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div class="font-bold">{{ $menu->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $menu->category?->name }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell>Rp {{ number_format($menu->price, 0, ',', '.') }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if($menu->image)
                                            <img src="{{ Storage::url($menu->image) }}" class="w-12 h-12 object-cover rounded">
                                        @else
                                            <span class="text-xs text-zinc-400">Tidak ada</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="{{ $menu->is_available ? 'green' : 'red' }}" size="sm" class="cursor-pointer" wire:click="toggleAvailability({{ $menu->id }})">
                                            {{ $menu->is_available ? 'Tersedia' : 'Habis' }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex gap-2">
                                            <flux:button size="sm" variant="outline" wire:click="editMenu({{ $menu->id }})">Edit</flux:button>
                                            <flux:button size="sm" variant="danger" wire:click="deleteMenu({{ $menu->id }})" wire:confirm="Hapus menu?">Hap</flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                    <div class="mt-4">{{ $menus->links() }}</div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
