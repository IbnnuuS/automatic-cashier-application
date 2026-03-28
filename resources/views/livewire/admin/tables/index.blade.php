<?php
use Livewire\Volt\Component;
use App\Models\Table;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $tableId = null;
    public $name = '';
    public $status = 'available';

    public function editTable($id)
    {
        $table = Table::findOrFail($id);
        $this->tableId = $id;
        $this->name = $table->name;
        $this->status = $table->status;
    }

    public function resetForm()
    {
        $this->tableId = null;
        $this->name = '';
        $this->status = 'available';
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:available,occupied',
        ]);

        $uuid = $this->tableId ? Table::find($this->tableId)->qr_code_token : (string) \Illuminate\Support\Str::uuid();

        Table::updateOrCreate(
            ['id' => $this->tableId],
            [
                'name' => $this->name,
                'status' => $this->status,
                'qr_code_token' => $uuid,
            ]
        );

        $this->resetForm();
    }

    public function deleteTable($id)
    {
        Table::findOrFail($id)->delete();
    }

    public function generateNewQr($id)
    {
        $table = Table::findOrFail($id);
        $table->update(['qr_code_token' => (string) \Illuminate\Support\Str::uuid()]);
    }

    public function with(): array
    {
        return [
            'tables' => Table::latest()->paginate(10),
        ];
    }
};
?>

<div class="w-full">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">Manajemen Meja</flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Section -->
            <flux:card class="h-fit">
                <flux:heading size="lg" class="mb-4">{{ $tableId ? 'Edit Meja' : 'Tambah Meja Baru' }}</flux:heading>
                <form wire:submit="store" class="space-y-4">
                    <flux:input wire:model="name" label="Nama Meja" placeholder="Misal: Meja VIP 1" required />
                    
                    <flux:select wire:model="status" label="Status Saat Ini" required>
                        <flux:select.option value="available">Kosong (Available)</flux:select.option>
                        <flux:select.option value="occupied">Terisi (Occupied)</flux:select.option>
                    </flux:select>
                    
                    <div class="flex items-center gap-2 pt-2">
                        @if($tableId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Batal</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full">{{ $tableId ? 'Update Data' : 'Simpan Meja' }}</flux:button>
                    </div>
                </form>
            </flux:card>

            <!-- Table Section -->
            <div class="lg:col-span-2">
                <flux:card>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nama Meja</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>QR Code</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($tables as $table)
                                <flux:table.row>
                                    <flux:table.cell class="font-bold">{{ $table->name }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="{{ $table->status === 'available' ? 'green' : 'orange' }}" size="sm" inset="top bottom">
                                            {{ ucfirst($table->status) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($table->qr_code_token)
                                            <div class="flex gap-2 items-center">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode(url('/order/' . $table->qr_code_token)) }}" alt="QR" class="w-12 h-12 rounded border p-1 bg-white" />
                                                <a href="{{ url('/order/' . $table->qr_code_token) }}" target="_blank" class="text-xs text-blue-500 hover:underline">Test Scan</a>
                                            </div>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="editTable({{ $table->id }})" icon="pencil">Edit Meja</flux:menu.item>
                                                <flux:menu.item wire:click="generateNewQr({{ $table->id }})" icon="arrow-path">Reset QR Code</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="deleteTable({{ $table->id }})" wire:confirm="Yakin menghapus meja ini?" icon="trash" variant="danger">Hapus</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                    <div class="mt-4">
                        {{ $tables->links() }}
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
