<div class="flex items-center gap-1.5">
    @foreach(config('app.supported_locales') as $loc)
        @if($loc === app()->getLocale())
            <span class="text-[10px] uppercase tracking-[0.15em] text-black font-medium">{{ $loc }}</span>
        @else
            <a href="{{ route('locale.set', $loc) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/25 hover:text-black transition-colors">{{ $loc }}</a>
        @endif
        @if(!$loop->last)
            <span class="text-black/10">·</span>
        @endif
    @endforeach
</div>
