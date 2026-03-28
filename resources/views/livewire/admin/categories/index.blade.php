<?php

use Livewire\Volt\Component;
use App\Models\Category;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $categoryId = null;
    public $name = '';

    public function editCategory($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $id;
        $this->name = $category->name;
    }

    public function resetForm()
    {
        $this->categoryId = null;
        $this->name = '';
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
            ]
        );

        $this->resetForm();
    }

    public function deleteCategory($id)
    {
        Category::findOrFail($id)->delete();
    }

    public function with(): array
    {
        return [
            'categories' => Category::latest()->paginate(10),
        ];
    }
};
?>

<div class="w-full">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">Kategori Menu</flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <flux:card class="h-fit">
                <flux:heading size="lg" class="mb-4">{{ $categoryId ? 'Edit Kategori' : 'Tambah Kategori' }}</flux:heading>
                <form wire:submit="store" class="space-y-4">
                    <flux:input wire:model="name" label="Nama Kategori" placeholder="Misal: Minuman Dingin" required />
                    
                    <div class="flex items-center gap-2 pt-2">
                        @if($categoryId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Batal</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full">{{ $categoryId ? 'Update Data' : 'Simpan Kategori' }}</flux:button>
                    </div>
                </form>
            </flux:card>

            <div class="lg:col-span-2">
                <flux:card>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nama Kategori</flux:table.column>
                            <flux:table.column>Slug URL</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($categories as $cat)
                                <flux:table.row>
                                    <flux:table.cell class="font-bold">{{ $cat->name }}</flux:table.cell>
                                    <flux:table.cell class="text-zinc-500">{{ $cat->slug }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex gap-2">
                                            <flux:button size="sm" variant="outline" wire:click="editCategory({{ $cat->id }})">Edit</flux:button>
                                            <flux:button size="sm" variant="danger" wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Hapus kategori?">Hapus</flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                    <div class="mt-4">{{ $categories->links() }}</div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
