@extends('layouts.app')

@section('content')
<div class="mb-12">
    <a href="{{ route('bots.show', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&larr; {{ $bot->name }}</a>
    <h1 class="font-serif text-3xl mt-3">{{ __('Editar') }}</h1>
</div>

<form method="POST" action="{{ route('bots.update', $bot) }}" class="max-w-xl">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <label for="name" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nombre') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name', $bot->name) }}" required
                class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
            @error('name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="entry_file" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Archivo de entrada') }}</label>
            <input type="text" name="entry_file" id="entry_file" value="{{ old('entry_file', $bot->entry_file) }}"
                class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 font-mono">
            @error('entry_file')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mb-8">
        <label for="description" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Descripcion') }}</label>
        <input type="text" name="description" id="description" value="{{ old('description', $bot->description) }}"
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
            placeholder="{{ __('Opcional') }}">
        @error('description')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    @if($bot->deploy_method === 'github')
    <div class="mb-8">
        <label for="repo_url" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('URL del repositorio') }}</label>
        <input type="text" name="repo_url" id="repo_url" value="{{ old('repo_url', $bot->repo_url) }}"
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 font-mono placeholder:text-black/20"
            placeholder="git@github.com:usuario/repo.git">
        @error('repo_url')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>
    @endif

    <div class="mb-8">
        <label for="env_vars" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Variables de entorno') }}</label>
        <textarea name="env_vars" id="env_vars" rows="4"
            placeholder="DISCORD_TOKEN=tu_token&#10;PREFIX=!"
            class="w-full bg-black/[0.02] border border-black/10 px-3 py-2 text-xs font-mono text-black/60 focus:border-black/30 focus:ring-0 resize-none placeholder:text-black/20">{{ old('env_vars', $bot->env_vars) }}</textarea>
        @error('env_vars')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    @if($bot->deploy_method === 'github')
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[10px] uppercase tracking-[0.15em] text-black/40">{{ __('Deploy Key SSH') }}</p>
            <span class="text-[10px] {{ $bot->deploy_key ? 'text-black/40' : 'text-black/20' }}" id="key-status">{{ $bot->deploy_key ? __('Configurada') : __('No configurada') }}</span>
        </div>

        <button type="button" id="btn-generate-key" onclick="generateSshKey()"
            class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/50 hover:border-black hover:text-black transition-colors mb-4">
            {{ $bot->deploy_key ? __('Regenerar') : __('Generar automaticamente') }}
        </button>

        <div id="key-generating" class="hidden mb-4">
            <p class="text-xs text-black/40 animate-pulse">{{ __('Generando claves...') }}</p>
        </div>

        <div id="public-key-section" class="hidden mb-4">
            <div class="border-s-2 border-black ps-4 py-2">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] uppercase tracking-[0.15em] text-black/40">{{ __('Clave publica') }}</p>
                    <button type="button" onclick="copyPublicKey()" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black"><span id="copy-text">{{ __('Copiar') }}</span></button>
                </div>
                <textarea id="public_key_display" readonly rows="2"
                    class="w-full bg-black/[0.02] border-0 px-3 py-2 text-[10px] font-mono text-black/60 focus:outline-none resize-none"></textarea>
                <p class="text-[10px] text-black/25 mt-2">{{ __('Pegala en GitHub → Settings → Deploy keys. Guarda para aplicar.') }}</p>
            </div>
        </div>

        <div id="key-error" class="hidden mb-4">
            <p class="text-xs text-red-500 border-s-2 border-red-500 ps-3" id="key-error-text"></p>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] text-black/25">{{ __('Clave privada (vacio = mantener actual)') }}</p>
                <button type="button" onclick="togglePrivateKeyVisibility()" class="text-[10px] text-black/25 hover:text-black" id="btn-toggle-pk">{{ __('Mostrar') }}</button>
            </div>
            <textarea name="deploy_key" id="deploy_key" rows="3"
                placeholder="{{ __('Se rellena al generar, o pega manualmente') }}"
                class="w-full bg-black/[0.02] border border-black/10 px-3 py-2 text-[10px] font-mono text-black/60 focus:border-black/30 focus:ring-0 resize-none transition-all"
                style="filter: blur(3px);" onfocus="this.style.filter='none'" onblur="if(this.value) this.style.filter='blur(3px)'"></textarea>
        </div>
        @error('deploy_key')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>
    @endif

    <!-- Deploy Info -->
    <div class="mb-10 border-t border-black/10 pt-6">
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/25 mb-3">{{ __('Info') }}</p>
        <div class="grid grid-cols-2 gap-y-2 text-xs">
            <span class="text-black/30">{{ __('Metodo') }}</span>
            <span>{{ $bot->deploy_method === 'github' ? 'GitHub' : 'ZIP' }}</span>
            @if($bot->repo_url)
                <span class="text-black/30">{{ __('Repo') }}</span>
                <span class="font-mono text-[10px] truncate">{{ $bot->repo_url }}</span>
            @endif
            <span class="text-black/30">{{ __('Ruta') }}</span>
            <span class="font-mono text-[10px]">{{ $bot->path }}</span>
            <span class="text-black/30">{{ __('Creado') }}</span>
            <span>{{ $bot->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-black/10 pt-6">
        <a href="{{ route('bots.show', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Cancelar') }}</a>
        <button type="submit" class="border border-black px-6 py-3 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Guardar') }}
        </button>
    </div>
</form>

@if($bot->deploy_method === 'github')
<script>
const _t = {
    regenerate: @json(__('Regenerar')),
    copied: @json(__('Copiado')),
    copy: @json(__('Copiar')),
    generationError: @json(__('Error al generar')),
    networkError: @json(__('Error de red')),
    pendingSave: @json(__('Pendiente de guardar')),
    show: @json(__('Mostrar')),
    hide: @json(__('Ocultar')),
};

function generateSshKey() {
    const btn = document.getElementById('btn-generate-key');
    const loading = document.getElementById('key-generating');
    const pubSection = document.getElementById('public-key-section');
    const errorSection = document.getElementById('key-error');
    const deployKeyField = document.getElementById('deploy_key');

    btn.disabled = true;
    btn.style.opacity = '0.3';
    loading.classList.remove('hidden');
    pubSection.classList.add('hidden');
    errorSection.classList.add('hidden');

    fetch('{{ route("generate-ssh-key") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        loading.classList.add('hidden');
        btn.disabled = false;
        btn.style.opacity = '';

        if (!ok || data.error) {
            errorSection.classList.remove('hidden');
            document.getElementById('key-error-text').textContent = data.error || _t.generationError;
            return;
        }

        deployKeyField.value = data.private_key;
        deployKeyField.style.filter = 'blur(3px)';
        document.getElementById('public_key_display').value = data.public_key;
        pubSection.classList.remove('hidden');
        document.getElementById('key-status').textContent = _t.pendingSave;
        btn.textContent = _t.regenerate;
    })
    .catch(() => {
        loading.classList.add('hidden');
        btn.disabled = false;
        btn.style.opacity = '';
        errorSection.classList.remove('hidden');
        document.getElementById('key-error-text').textContent = _t.networkError;
    });
}

function copyPublicKey() {
    const pubKey = document.getElementById('public_key_display');
    pubKey.select();
    navigator.clipboard.writeText(pubKey.value).then(() => {
        document.getElementById('copy-text').textContent = _t.copied;
        setTimeout(() => { document.getElementById('copy-text').textContent = _t.copy; }, 2000);
    });
}

function togglePrivateKeyVisibility() {
    const field = document.getElementById('deploy_key');
    const btn = document.getElementById('btn-toggle-pk');
    if (field.style.filter === 'none' || !field.style.filter) {
        field.style.filter = 'blur(3px)';
        btn.textContent = _t.show;
    } else {
        field.style.filter = 'none';
        btn.textContent = _t.hide;
    }
}
</script>
@endif
@endsection
