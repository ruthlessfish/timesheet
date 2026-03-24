<form action="{{ $url }}" method="POST" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="inline-flex text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
        onclick="return confirm('{{ $confirmText }}')">
        @if($showIcon === true)
        <x-heroicons::mini.solid.trash class="mr-2" />
        @endif
        @if($showText === true)
        {{ $label }}
        @endif
    </button>
</form>