<div x-data="themeToggle()" class="relative">
    <div class="flex items-center gap-1 p-1">
        <!-- Light Mode Button -->
        <button @click="setTheme('light')" type="button" 
                :class="theme === 'light' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="p-2 rounded-md transition-all duration-200"
                title="Light mode">
            <svg class="w-4 h-4" :class="theme === 'light' ? 'text-yellow-500' : 'text-gray-600 dark:text-gray-400'" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </button>
        
        <!-- Dark Mode Button -->
        <button @click="setTheme('dark')" type="button"
                :class="theme === 'dark' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="p-2 rounded-md transition-all duration-200"
                title="Dark mode">
            <svg class="w-4 h-4" :class="theme === 'dark' ? 'text-indigo-500' : 'text-gray-600 dark:text-gray-400'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
        
        <!-- System Mode Button -->
        <button @click="setTheme('system')" type="button"
                :class="theme === 'system' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="p-2 rounded-md transition-all duration-200"
                title="System preference">
            <svg class="w-4 h-4" :class="theme === 'system' ? 'text-blue-500' : 'text-gray-600 dark:text-gray-400'"
                 fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" fill="currentColor"/>
                <path d="M12 2 A 10 10 0 0 1 12 22 Z" fill="white"/>
            </svg>
        </button>
    </div>
</div>

<script>
function themeToggle() {
    return {
        theme: '{{ auth()->user()->theme_preference ?? "system" }}',
        
        init() {
            this.applyTheme();
            
            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });
        },
        
        setTheme(newTheme) {
            this.theme = newTheme;
            localStorage.setItem('theme', newTheme);
            this.applyTheme();
            
            // Save to server
            fetch('/settings/theme', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ theme: newTheme })
            });
        },
        
        applyTheme() {
            let shouldBeDark = false;
            
            if (this.theme === 'dark') {
                shouldBeDark = true;
            } else if (this.theme === 'light') {
                shouldBeDark = false;
            } else {
                // system
                shouldBeDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }
}
</script>