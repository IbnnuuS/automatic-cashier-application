<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role_id = '';

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->password = ''; 
    }

    public function resetForm()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'role_id']);
    }

    public function store()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'role_id' => 'required|exists:roles,id',
        ];

        if (!$this->userId) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->userId], $data);
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        if(auth()->id() == $id) {
            return;
        }
        User::findOrFail($id)->delete();
    }

    public function with(): array
    {
        return [
            'usersList' => User::with('role')->latest()->paginate(10),
            'roles' => Role::all(),
        ];
    }
};
?>

<div class="w-full">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">Manajemen Staff & Pengguna</flux:heading>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <flux:card class="h-fit">
                <flux:heading size="lg" class="mb-4">{{ $userId ? 'Edit Pengguna' : 'Tambah Pengguna' }}</flux:heading>
                <form wire:submit="store" class="space-y-4">
                    <flux:input wire:model="name" label="Nama Lengkap" required />
                    <flux:input type="email" wire:model="email" label="Email Login" required />
                    
                    <flux:select wire:model="role_id" label="Role Pekerjaan" required>
                        <flux:select.option value="">-- Pilih Role --</flux:select.option>
                        @foreach($roles as $role)
                            <flux:select.option value="{{ $role->id }}">{{ ucfirst($role->name) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input type="password" wire:model="password" label="Password" placeholder="{{ $userId ? '(Kosongkan jika tidak ingin ubah)' : 'Min. 6 Karakter' }}" />
                    
                    <div class="flex items-center gap-2 pt-2">
                        @if($userId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Batal</flux:button>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full">{{ $userId ? 'Update Pengguna' : 'Simpan Pengguna' }}</flux:button>
                    </div>
                </form>
            </flux:card>

            <div class="lg:col-span-2">
                <flux:card>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nama & Email</flux:table.column>
                            <flux:table.column>Role</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($usersList as $user)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div class="font-bold">{{ $user->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="blue" size="sm">{{ ucfirst($user->role?->name ?? 'Unknown') }}</flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex gap-2">
                                            <flux:button size="sm" variant="outline" wire:click="editUser({{ $user->id }})">Edit</flux:button>
                                            @if(auth()->id() !== $user->id)
                                                <flux:button size="sm" variant="danger" wire:click="deleteUser({{ $user->id }})" wire:confirm="Hapus user ini?">Hapus</flux:button>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                    <div class="mt-4">{{ $usersList->links() }}</div>
                </flux:card>
            </div>
        </div>
    </div>
</div>
