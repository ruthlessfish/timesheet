<form action="{{ route($route, $resource) }}" method="POST" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
        onclick="return confirm('Are you sure?')">Delete</button>
</form>