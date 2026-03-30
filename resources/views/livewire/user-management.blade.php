<div>
    <div class="flex items-end justify-between mb-12">
        <div>
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">{{ __('Admin') }}</p>
            <h1 class="font-serif text-3xl">{{ __('Usuarios') }}</h1>
        </div>
        <button wire:click="$toggle('showForm')" class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ $showForm ? __('Cancelar') : __('Nuevo usuario') }}
        </button>
    </div>

    @if($notification)
    <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.opacity>
        <p class="text-xs {{ $notificationType === 'error' ? 'text-red-600 border-s-2 border-red-600' : 'text-black/60 border-s-2 border-black' }} ps-3">{{ $notification }}</p>
    </div>
    @endif

    <!-- Create User Form -->
    @if($showForm)
    <div class="border border-black/10 p-6 mb-10 max-w-md">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Crear usuario') }}</p>

        <form wire:submit="createUser" class="space-y-5">
            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nombre') }}</label>
                <input type="text" wire:model="newName" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
                    placeholder="{{ __('Nombre') }}">
                @error('newName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">Email</label>
                <input type="email" wire:model="newEmail" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
                    placeholder="email@ejemplo.com">
                @error('newEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Contrasena') }}</label>
                <input type="password" wire:model="newPassword" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
                @error('newPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" class="border border-black px-5 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="createUser">{{ __('Crear') }}</span>
                    <span wire:loading wire:target="createUser">{{ __('Creando...') }}</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Users Table -->
    <div class="border-t border-black/10">
        @foreach($users as $user)
        <div class="flex items-center justify-between py-5 border-b border-black/10" wire:key="user-{{ $user->id }}">
            <div class="flex items-center gap-5">
                @if($user->is_admin)
                    <span class="h-1.5 w-1.5 rounded-full bg-black"></span>
                @else
                    <span class="h-1.5 w-1.5 rounded-full bg-black/15"></span>
                @endif
                <div>
                    <span class="text-sm">{{ $user->name }}</span>
                    @if($user->is_admin)
                        <span class="text-[9px] uppercase tracking-[0.15em] text-black/30 ms-2">admin</span>
                    @endif
                </div>
                <span class="text-xs text-black/30">{{ $user->email }}</span>
                <span class="text-[10px] text-black/20">{{ $user->bots_count }} {{ $user->bots_count === 1 ? 'bot' : 'bots' }}</span>
            </div>

            <div class="flex items-center gap-3">
                @if($user->id !== auth()->id())
                    <button wire:click="toggleAdmin({{ $user->id }})" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">
                        {{ $user->is_admin ? __('Quitar admin') : __('Hacer admin') }}
                    </button>
                    <button wire:click="deleteUser({{ $user->id }})" wire:confirm="{{ __('Eliminar a :name? Se eliminaran todos sus bots.', ['name' => $user->name]) }}" class="text-[10px] uppercase tracking-[0.15em] text-red-400 hover:text-red-600">
                        {{ __('Eliminar') }}
                    </button>
                @else
                    <span class="text-[10px] text-black/20">{{ __('Tu') }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
