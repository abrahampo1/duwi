<div>
    <div class="mb-12">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">{{ __('Cuenta') }}</p>
        <h1 class="font-serif text-3xl">{{ __('Ajustes') }}</h1>
    </div>

    @if($notification)
    <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.opacity>
        <p class="text-xs {{ $notificationType === 'error' ? 'text-red-600 border-s-2 border-red-600' : 'text-black/60 border-s-2 border-black' }} ps-3">{{ $notification }}</p>
    </div>
    @endif

    <!-- Change Email -->
    <div class="border border-black/10 p-6 mb-8 max-w-md">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Cambiar email') }}</p>

        <form wire:submit="updateEmail" class="space-y-5">
            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nuevo email') }}</label>
                <input type="email" wire:model="email" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
                    placeholder="email@ejemplo.com">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Contrasena actual') }}</label>
                <input type="password" wire:model="currentPasswordForEmail" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
                @error('currentPasswordForEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" class="border border-black px-5 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="updateEmail">{{ __('Actualizar email') }}</span>
                    <span wire:loading wire:target="updateEmail">{{ __('Actualizando...') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="border border-black/10 p-6 max-w-md">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Cambiar contrasena') }}</p>

        <form wire:submit="updatePassword" class="space-y-5">
            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Contrasena actual') }}</label>
                <input type="password" wire:model="currentPassword" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
                @error('currentPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nueva contrasena') }}</label>
                <input type="password" wire:model="newPassword" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
                @error('newPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Confirmar contrasena') }}</label>
                <input type="password" wire:model="newPassword_confirmation" required
                    class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" class="border border-black px-5 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="updatePassword">{{ __('Actualizar contrasena') }}</span>
                    <span wire:loading wire:target="updatePassword">{{ __('Actualizando...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
