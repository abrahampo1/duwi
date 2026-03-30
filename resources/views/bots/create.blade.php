@extends('layouts.app')

@section('content')
<div class="mb-12">
    <a href="{{ route('bots.index') }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&larr; {{ __('Bots') }}</a>
    <h1 class="font-serif text-3xl mt-3">{{ __('Nuevo bot') }}</h1>
</div>

<form method="POST" action="{{ route('bots.store') }}" enctype="multipart/form-data" class="max-w-xl">
    @csrf

    <!-- Name & Entry File -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <label for="name" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nombre') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
                placeholder="Mi bot">
            @error('name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="entry_file" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Archivo de entrada') }}</label>
            <input type="text" name="entry_file" id="entry_file" value="{{ old('entry_file', 'index.js') }}"
                class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 font-mono placeholder:text-black/20">
            @error('entry_file')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Description -->
    <div class="mb-8">
        <label for="description" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Descripcion') }}</label>
        <input type="text" name="description" id="description" value="{{ old('description') }}"
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
            placeholder="{{ __('Opcional') }}">
        @error('description')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- Deploy Method -->
    <div class="mb-8">
        <p class="text-[10px] uppercase tracking-[0.15em] text-black/40 mb-4">{{ __('Metodo de deploy') }}</p>
        <div class="flex gap-0">
            <label class="flex-1 cursor-pointer border border-black/15 py-3 text-center transition-colors" id="label-github">
                <input type="radio" name="deploy_method" value="github" {{ old('deploy_method', 'github') === 'github' ? 'checked' : '' }}
                    class="sr-only" onchange="toggleDeployMethod()">
                <span class="text-[10px] uppercase tracking-[0.15em]">GitHub</span>
            </label>
            <label class="flex-1 cursor-pointer border border-s-0 border-black/15 py-3 text-center transition-colors" id="label-zip">
                <input type="radio" name="deploy_method" value="zip" {{ old('deploy_method') === 'zip' ? 'checked' : '' }}
                    class="sr-only" onchange="toggleDeployMethod()">
                <span class="text-[10px] uppercase tracking-[0.15em]">{{ __('Subir ZIP') }}</span>
            </label>
        </div>
        @error('deploy_method')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- GitHub Section -->
    <div id="github-section" class="mb-8 space-y-6">
        <div>
            <label for="repo_url" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('URL del repositorio') }}</label>
            <input type="text" name="repo_url" id="repo_url" value="{{ old('repo_url') }}"
                class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20 font-mono"
                placeholder="git@github.com:usuario/repo.git">
            <p class="mt-1 text-[10px] text-black/25">{{ __('SSH para repos privados, HTTPS para publicos') }}</p>
            @error('repo_url')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Deploy Key -->
        <div>
            <p class="text-[10px] uppercase tracking-[0.15em] text-black/40 mb-3">{{ __('Deploy Key SSH') }}</p>

            <button type="button" id="btn-generate-key" onclick="generateSshKey()"
                class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/50 hover:border-black hover:text-black transition-colors mb-4">
                {{ __('Generar automaticamente') }}
            </button>

            <!-- Loading -->
            <div id="key-generating" class="hidden mb-4">
                <p class="text-xs text-black/40 animate-pulse">{{ __('Generando claves...') }}</p>
            </div>

            <!-- Public Key -->
            <div id="public-key-section" class="hidden mb-4">
                <div class="border-s-2 border-black ps-4 py-2">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] uppercase tracking-[0.15em] text-black/40">{{ __('Clave publica') }}</p>
                        <button type="button" onclick="copyPublicKey()" id="btn-copy-pubkey"
                            class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">
                            <span id="copy-text">{{ __('Copiar') }}</span>
                        </button>
                    </div>
                    <textarea id="public_key_display" readonly rows="2"
                        class="w-full bg-black/[0.02] border-0 px-3 py-2 text-[10px] font-mono text-black/60 focus:outline-none resize-none"></textarea>
                    <p class="text-[10px] text-black/25 mt-2">{{ __('Copia y pegala en GitHub → Settings → Deploy keys') }}</p>
                </div>
            </div>

            <!-- Error -->
            <div id="key-error" class="hidden mb-4">
                <p class="text-xs text-red-500 border-s-2 border-red-500 ps-3" id="key-error-text"></p>
            </div>

            <!-- Private Key -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] text-black/25">{{ __('Clave privada') }}</p>
                    <button type="button" onclick="togglePrivateKeyVisibility()" class="text-[10px] text-black/25 hover:text-black" id="btn-toggle-pk">{{ __('Mostrar') }}</button>
                </div>
                <textarea name="deploy_key" id="deploy_key" rows="3"
                    placeholder="{{ __('Se rellena al generar, o pega manualmente') }}"
                    class="w-full bg-black/[0.02] border border-black/10 px-3 py-2 text-[10px] font-mono text-black/60 focus:border-black/30 focus:ring-0 resize-none transition-all"
                    style="filter: blur(3px);" onfocus="this.style.filter='none'" onblur="if(this.value) this.style.filter='blur(3px)'">{{ old('deploy_key') }}</textarea>
            </div>
            @error('deploy_key')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- ZIP Section -->
    <div id="zip-section" class="hidden mb-8">
        <label for="zip_file" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-3">{{ __('Archivo ZIP') ?? 'Archivo ZIP' }}</label>
        <div class="border border-dashed border-black/15 py-10 text-center">
            <label for="zip_file" class="cursor-pointer border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/50 hover:border-black hover:text-black transition-colors">
                {{ __('Seleccionar archivo') }}
                <input id="zip_file" name="zip_file" type="file" accept=".zip" class="sr-only">
            </label>
            <p class="mt-3 text-[10px] text-black/20" id="file-name">{{ __('Hasta 100MB') }}</p>
        </div>
        @error('zip_file')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- Environment Variables -->
    <div class="mb-10">
        <label for="env_vars" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Variables de entorno') }}</label>
        <textarea name="env_vars" id="env_vars" rows="3"
            placeholder="DISCORD_TOKEN=tu_token&#10;PREFIX=!&#10;NODE_ENV=production"
            class="w-full bg-black/[0.02] border border-black/10 px-3 py-2 text-xs font-mono text-black/60 focus:border-black/30 focus:ring-0 resize-none placeholder:text-black/20">{{ old('env_vars') }}</textarea>
        @error('env_vars')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between border-t border-black/10 pt-6">
        <a href="{{ route('bots.index') }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Cancelar') }}</a>
        <button type="submit" class="border border-black px-6 py-3 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Crear bot') }}
        </button>
    </div>
</form>

<script>
const _t = {
    regenerate: @json(__('Regenerar')),
    copied: @json(__('Copiado')),
    copy: @json(__('Copiar')),
    generationError: @json(__('Error al generar')),
    networkError: @json(__('Error de red')),
    show: @json(__('Mostrar')),
    hide: @json(__('Ocultar')),
    upTo100MB: @json(__('Hasta 100MB')),
};

function toggleDeployMethod() {
    const method = document.querySelector('input[name="deploy_method"]:checked').value;
    const githubSection = document.getElementById('github-section');
    const zipSection = document.getElementById('zip-section');
    const labelGithub = document.getElementById('label-github');
    const labelZip = document.getElementById('label-zip');

    if (method === 'github') {
        githubSection.classList.remove('hidden');
        zipSection.classList.add('hidden');
        labelGithub.style.backgroundColor = '#000';
        labelGithub.style.color = '#fff';
        labelZip.style.backgroundColor = '';
        labelZip.style.color = '';
    } else {
        githubSection.classList.add('hidden');
        zipSection.classList.remove('hidden');
        labelZip.style.backgroundColor = '#000';
        labelZip.style.color = '#fff';
        labelGithub.style.backgroundColor = '';
        labelGithub.style.color = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleDeployMethod();

    document.getElementById('zip_file').addEventListener('change', function() {
        document.getElementById('file-name').textContent = this.files[0] ? this.files[0].name : _t.upTo100MB;
    });

    const dk = document.getElementById('deploy_key');
    if (!dk.value.trim()) dk.style.filter = 'none';
});

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
@endsection
