# Phase 5: Polish & UX - Implementation Plan

**Timeline**: 6-8 weeks (30-40 working days)  
**Focus**: Improve existing features, make app delightful  
**Start Date**: January 27, 2026 (planned)  
**Target Completion**: Mid-March 2026

---

## 📋 Overview

Phase 5 focuses on user experience improvements that make the app more pleasant and efficient to use. These are "polish" features that don't add major functionality but significantly improve daily usage.

### Goals
- ✅ Reduce friction in common workflows
- ✅ Support power users with keyboard shortcuts
- ✅ Improve visual design with dark mode
- ✅ Add visual calendar for better time overview
- ✅ Enable batch operations for efficiency
- ✅ Support reusable time entry patterns

### Success Metrics
- User retention increase by 20%
- Average session time increase by 15%
- Positive feedback on UX improvements
- Reduction in support requests for common tasks

---

## 🎯 Feature 1: Dark Mode Toggle

**Estimated Time**: 3-4 days  
**Priority**: High (most requested feature)  
**Complexity**: Low-Medium

### Technical Requirements
- Tailwind CSS dark mode support (already available)
- User preference storage (database or localStorage)
- System preference detection
- Smooth theme transitions

### Implementation Steps

#### 1.1 Database Changes (Day 1)
```bash
php artisan make:migration add_theme_preference_to_users_table
```

**Migration**:
```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('theme_preference', ['light', 'dark', 'system'])
        ->default('system')
        ->after('email_verified_at');
});
```

**Model Update** (`app/Models/User.php`):
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'theme_preference',
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
];
```

#### 1.2 Tailwind Configuration (Day 1)
Update `tailwind.config.js`:
```javascript
module.exports = {
  darkMode: 'class', // Enable class-based dark mode
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  // ... rest of config
}
```

#### 1.3 Backend Implementation (Day 2)
Create settings controller:
```bash
php artisan make:controller Settings/ThemeController
```

**Routes** (`routes/web.php`):
```php
Route::middleware('auth')->group(function () {
    Route::patch('/settings/theme', [ThemeController::class, 'update'])
        ->name('settings.theme.update');
});
```

**Controller** (`app/Http/Controllers/Settings/ThemeController.php`):
```php
public function update(Request $request)
{
    $validated = $request->validate([
        'theme' => 'required|in:light,dark,system',
    ]);
    
    $request->user()->update([
        'theme_preference' => $validated['theme'],
    ]);
    
    return back()->with('success', 'Theme updated successfully.');
}
```

#### 1.4 Frontend Implementation (Day 2-3)
**Layout Update** (`resources/views/layouts/app.blade.php`):
```html
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      class="{{ auth()->user()?->theme_preference === 'dark' ? 'dark' : '' }}"
      x-data="{ 
          darkMode: {{ auth()->user()?->theme_preference === 'dark' ? 'true' : 'false' }},
          toggleDark() {
              this.darkMode = !this.darkMode;
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }">
```

**Theme Toggle Component** (`resources/views/components/theme-toggle.blade.php`):
```html
<div x-data="themeToggle()" class="relative">
    <button @click="toggle" type="button" 
            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition">
        <!-- Sun Icon (Light Mode) -->
        <svg x-show="!isDark" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        
        <!-- Moon Icon (Dark Mode) -->
        <svg x-show="isDark" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
</div>

<script>
function themeToggle() {
    return {
        isDark: localStorage.getItem('theme') === 'dark' || 
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        
        toggle() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Save to server
            fetch('/settings/theme', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ theme: this.isDark ? 'dark' : 'light' })
            });
        }
    }
}
</script>
```

#### 1.5 Update Existing Components (Day 3-4)
Add `dark:` variants to all components:
- `dark:bg-gray-800` for backgrounds
- `dark:text-gray-200` for text
- `dark:border-gray-700` for borders
- `dark:hover:bg-gray-700` for hovers

**Priority Components to Update**:
1. Dashboard cards
2. Navigation bar
3. Forms and inputs
4. Tables (time entries, invoices)
5. Modals and dropdowns
6. Buttons (already styled via components)

#### 1.6 Testing (Day 4)
```bash
php artisan make:test Settings/ThemePreferenceTest
```

**Tests**:
- ✅ User can update theme preference
- ✅ Theme persists across sessions
- ✅ System preference detection works
- ✅ Theme toggle responds immediately
- ✅ Dark mode classes applied correctly

---

## ⌨️ Feature 2: Keyboard Shortcuts

**Estimated Time**: 4-5 days  
**Priority**: High (power user feature)  
**Complexity**: Medium

### Technical Requirements
- Global keyboard event listener
- Shortcut registry system
- Visual shortcut help modal
- Conflict prevention
- Accessibility considerations

### Planned Shortcuts

| Shortcut | Action | Context |
|----------|--------|---------|
| `Ctrl/Cmd + T` | Start timer (first project) | Global |
| `Ctrl/Cmd + S` | Stop active timer | When timer running |
| `Ctrl/Cmd + N` | New time entry | Time entries page |
| `Ctrl/Cmd + E` | Edit selected entry | Time entry selected |
| `Ctrl/Cmd + /` | Show shortcuts help | Global |
| `Ctrl/Cmd + K` | Quick search/command palette | Global |
| `Esc` | Close modals | When modal open |
| `Arrow Keys` | Navigate entries | Time entries list |

### Implementation Steps

#### 2.1 Keyboard Manager Service (Day 1)
Create JavaScript module: `resources/js/keyboard-manager.js`

```javascript
class KeyboardShortcutManager {
    constructor() {
        this.shortcuts = new Map();
        this.enabled = true;
        this.init();
    }
    
    init() {
        document.addEventListener('keydown', this.handleKeyPress.bind(this));
    }
    
    register(key, modifiers, callback, context = 'global') {
        const shortcutKey = this.createKey(key, modifiers, context);
        this.shortcuts.set(shortcutKey, callback);
    }
    
    createKey(key, modifiers, context) {
        const parts = [];
        if (modifiers.ctrl) parts.push('ctrl');
        if (modifiers.shift) parts.push('shift');
        if (modifiers.alt) parts.push('alt');
        if (modifiers.meta) parts.push('meta');
        parts.push(key.toLowerCase());
        parts.push(context);
        return parts.join('+');
    }
    
    handleKeyPress(event) {
        if (!this.enabled) return;
        
        // Don't trigger shortcuts when typing in inputs
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
            if (event.key !== 'Escape') return;
        }
        
        const modifiers = {
            ctrl: event.ctrlKey || event.metaKey, // Mac uses Cmd (metaKey)
            shift: event.shiftKey,
            alt: event.altKey,
            meta: event.metaKey,
        };
        
        const currentContext = this.getCurrentContext();
        const shortcutKey = this.createKey(event.key, modifiers, currentContext);
        const globalKey = this.createKey(event.key, modifiers, 'global');
        
        const callback = this.shortcuts.get(shortcutKey) || this.shortcuts.get(globalKey);
        
        if (callback) {
            event.preventDefault();
            callback(event);
        }
    }
    
    getCurrentContext() {
        // Determine context from current page
        const path = window.location.pathname;
        if (path.includes('/time-entries')) return 'time-entries';
        if (path.includes('/invoices')) return 'invoices';
        if (path.includes('/projects')) return 'projects';
        if (path.includes('/clients')) return 'clients';
        return 'global';
    }
    
    disable() {
        this.enabled = false;
    }
    
    enable() {
        this.enabled = true;
    }
}

// Export singleton instance
window.keyboardManager = new KeyboardShortcutManager();
```

#### 2.2 Register Global Shortcuts (Day 2)
Update `resources/js/app.js`:

```javascript
import './bootstrap';
import './keyboard-manager';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Register global shortcuts
document.addEventListener('DOMContentLoaded', () => {
    const km = window.keyboardManager;
    
    // Start timer
    km.register('t', { ctrl: true }, () => {
        const startButton = document.getElementById('startTimerBtn');
        if (startButton && !startButton.disabled) {
            startButton.click();
        }
    });
    
    // Stop timer
    km.register('s', { ctrl: true }, () => {
        const stopButton = document.querySelector('[data-action="stop-timer"]');
        if (stopButton) {
            stopButton.click();
        }
    });
    
    // Show help modal
    km.register('/', { ctrl: true }, () => {
        Alpine.store('shortcuts').open();
    });
    
    // Quick search (future feature)
    km.register('k', { ctrl: true }, () => {
        // TODO: Open command palette
        console.log('Command palette - coming soon!');
    });
});
```

#### 2.3 Shortcuts Help Modal (Day 3)
Create component: `resources/views/components/shortcuts-modal.blade.php`

```html
<div x-data="{ open: false }" 
     @keydown.ctrl.slash.window="open = true"
     @keydown.escape.window="open = false">
    
    <!-- Trigger Button (in header) -->
    <button @click="open = true" 
            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
            title="Keyboard Shortcuts (Ctrl+/)">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
        </svg>
    </button>
    
    <!-- Modal -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="open = false"></div>
        
        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Keyboard Shortcuts
                    </h2>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- Global Shortcuts -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Global</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Start timer</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Ctrl + T
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Stop timer</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Ctrl + S
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Show shortcuts</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Ctrl + /
                                </kbd>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Time Entries -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Time Entries</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">New entry</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Ctrl + N
                                </kbd>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Edit selected</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Ctrl + E
                                </kbd>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Navigation</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Close modal</span>
                                <kbd class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    Esc
                                </kbd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                        <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">Ctrl</kbd> 
                        on Windows/Linux, 
                        <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">⌘ Cmd</kbd> 
                        on Mac
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
```

#### 2.4 Page-Specific Shortcuts (Day 4)
**Time Entries Index** (`resources/views/time-entries/index.blade.php`):
```javascript
<script>
document.addEventListener('DOMContentLoaded', () => {
    const km = window.keyboardManager;
    
    // New time entry
    km.register('n', { ctrl: true }, () => {
        window.location.href = '{{ route("time-entries.create") }}';
    }, 'time-entries');
    
    // Edit selected entry
    km.register('e', { ctrl: true }, () => {
        const selected = document.querySelector('[data-selected="true"]');
        if (selected) {
            const editUrl = selected.dataset.editUrl;
            window.location.href = editUrl;
        }
    }, 'time-entries');
});
</script>
```

#### 2.5 Testing (Day 5)
- ✅ All shortcuts trigger correct actions
- ✅ Shortcuts don't interfere with form inputs
- ✅ Help modal displays correctly
- ✅ Mac Cmd key works like Ctrl
- ✅ Shortcuts disabled when appropriate

---

## 🔔 Feature 3: Timer Notifications

**Estimated Time**: 3-4 days  
**Priority**: Medium  
**Complexity**: Low-Medium

### Technical Requirements
- Browser Notification API
- Permission management UI
- Notification preferences in database
- Background timer tracking
- Sound notifications (optional)

### Implementation Steps

#### 3.1 Database Changes (Day 1)
```bash
php artisan make:migration add_notification_preferences_to_users_table
```

**Migration**:
```php
Schema::table('users', function (Blueprint $table) {
    $table->json('notification_preferences')->nullable()->after('theme_preference');
});
```

**Default preferences structure**:
```json
{
  "timer_notifications": true,
  "thresholds": [3600, 7200, 14400, 28800], // 1h, 2h, 4h, 8h in seconds
  "sound_enabled": true,
  "notification_enabled": true
}
```

#### 3.2 Notification Manager (Day 1-2)
Create `resources/js/notification-manager.js`:

```javascript
class TimerNotificationManager {
    constructor() {
        this.permission = Notification.permission;
        this.thresholds = [3600, 7200, 14400, 28800]; // seconds
        this.notifiedThresholds = new Set();
        this.checkInterval = null;
    }
    
    async requestPermission() {
        if (this.permission === 'granted') return true;
        
        const result = await Notification.requestPermission();
        this.permission = result;
        return result === 'granted';
    }
    
    startMonitoring(timerStartTime) {
        this.notifiedThresholds.clear();
        
        // Check every minute
        this.checkInterval = setInterval(() => {
            this.checkThresholds(timerStartTime);
        }, 60000);
        
        // Check immediately
        this.checkThresholds(timerStartTime);
    }
    
    stopMonitoring() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
        this.notifiedThresholds.clear();
    }
    
    checkThresholds(startTime) {
        if (this.permission !== 'granted') return;
        
        const elapsed = Math.floor((Date.now() - new Date(startTime)) / 1000);
        
        for (const threshold of this.thresholds) {
            if (elapsed >= threshold && !this.notifiedThresholds.has(threshold)) {
                this.notify(threshold);
                this.notifiedThresholds.add(threshold);
            }
        }
    }
    
    notify(thresholdSeconds) {
        const hours = Math.floor(thresholdSeconds / 3600);
        const minutes = Math.floor((thresholdSeconds % 3600) / 60);
        
        let message;
        if (hours > 0) {
            message = `Your timer has been running for ${hours} hour${hours > 1 ? 's' : ''}`;
        } else {
            message = `Your timer has been running for ${minutes} minutes`;
        }
        
        const notification = new Notification('Timer Alert', {
            body: message,
            icon: '/images/logo.png', // Add your app icon
            badge: '/images/badge.png',
            tag: `timer-${thresholdSeconds}`,
            requireInteraction: false,
        });
        
        // Auto-close after 5 seconds
        setTimeout(() => notification.close(), 5000);
        
        // Optional: Play sound
        if (window.notificationSound) {
            window.notificationSound.play();
        }
    }
    
    showTestNotification() {
        if (this.permission === 'granted') {
            new Notification('Test Notification', {
                body: 'Notifications are working correctly!',
                icon: '/images/logo.png',
            });
        }
    }
}

window.timerNotifications = new TimerNotificationManager();
```

#### 3.3 Integrate with Dashboard (Day 2)
Update `resources/views/dashboard.blade.php`:

```javascript
<script>
@if($activeTimer)
    // Start monitoring active timer
    const startTime = '{{ $activeTimer->start_time }}';
    window.timerNotifications.startMonitoring(startTime);
@endif

// When timer starts via API
async function startTimer(projectId) {
    // ... existing code ...
    
    if (response.ok) {
        const data = await response.json();
        
        // Request notification permission if not granted
        if (Notification.permission === 'default') {
            await window.timerNotifications.requestPermission();
        }
        
        // Start monitoring
        window.timerNotifications.startMonitoring(data.data.start_time);
        
        window.location.reload();
    }
}

// Stop monitoring when timer stops
window.addEventListener('beforeunload', () => {
    window.timerNotifications.stopMonitoring();
});
</script>
```

#### 3.4 Settings UI (Day 3)
Create settings page: `resources/views/settings/notifications.blade.php`

```html
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notification Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('settings.notifications.update') }}" 
                          x-data="notificationSettings()">
                        @csrf
                        @method('PATCH')
                        
                        <!-- Browser Notifications -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="notifications_enabled" 
                                       x-model="enabled"
                                       @change="updatePermission()"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                    Enable browser notifications
                                </span>
                            </label>
                            
                            <button type="button" @click="testNotification()" 
                                    class="mt-2 text-sm text-indigo-600 hover:text-indigo-500">
                                Send test notification
                            </button>
                        </div>
                        
                        <!-- Timer Thresholds -->
                        <div class="mb-6" x-show="enabled">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notify me when timer reaches:
                            </label>
                            
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="thresholds[]" value="3600" 
                                           class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">1 hour</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="thresholds[]" value="7200" checked
                                           class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">2 hours</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="thresholds[]" value="14400" checked
                                           class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">4 hours</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="thresholds[]" value="28800"
                                           class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">8 hours</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Sound -->
                        <div class="mb-6" x-show="enabled">
                            <label class="flex items-center">
                                <input type="checkbox" name="sound_enabled" checked
                                       class="rounded border-gray-300 text-indigo-600">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                    Play sound with notifications
                                </span>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-end">
                            <x-primary-button>
                                {{ __('Save Settings') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function notificationSettings() {
    return {
        enabled: {{ auth()->user()->notification_preferences['notification_enabled'] ?? 'false' }},
        
        async updatePermission() {
            if (this.enabled && Notification.permission !== 'granted') {
                const result = await Notification.requestPermission();
                if (result !== 'granted') {
                    this.enabled = false;
                    alert('Please allow notifications in your browser settings');
                }
            }
        },
        
        testNotification() {
            window.timerNotifications.showTestNotification();
        }
    }
}
</script>
```

#### 3.5 Testing (Day 4)
- ✅ Permission request works
- ✅ Notifications appear at thresholds
- ✅ Settings persist correctly
- ✅ No duplicate notifications
- ✅ Works across browser tabs

---

## 📅 Feature 4: Calendar View

**Estimated Time**: 7-10 days  
**Priority**: High (great visual overview)  
**Complexity**: High

### Technical Requirements
- Calendar library (FullCalendar.js or custom)
- Month/week view support
- Click to view/edit entries
- Color coding by project
- Summary statistics per day

### Implementation Steps

#### 4.1 Install FullCalendar (Day 1)
```bash
npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/interaction
```

#### 4.2 Create Calendar Route & Controller (Day 1-2)
**Routes**:
```php
Route::middleware('auth')->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/entries', [CalendarController::class, 'entries'])->name('calendar.entries');
});
```

**Controller**:
```php
<?php

namespace App\Http\Controllers;

use App\Services\TimeEntryService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        private TimeEntryService $timeEntryService
    ) {}
    
    public function index()
    {
        return view('calendar.index');
    }
    
    public function entries(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        
        $entries = $this->timeEntryService->getEntriesForUser(
            userId: auth()->id(),
            filters: [
                'start_date' => $start,
                'end_date' => $end,
            ]
        );
        
        // Transform for FullCalendar
        $events = $entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'title' => $entry->project->name . ($entry->description ? ': ' . $entry->description : ''),
                'start' => $entry->start_time,
                'end' => $entry->end_time,
                'backgroundColor' => $this->getProjectColor($entry->project_id),
                'borderColor' => $this->getProjectColor($entry->project_id),
                'extendedProps' => [
                    'projectName' => $entry->project->name,
                    'clientName' => $entry->project->client->name,
                    'duration' => $entry->duration,
                    'amount' => $entry->amount,
                    'isBillable' => $entry->is_billable,
                ],
            ];
        });
        
        return response()->json($events);
    }
    
    private function getProjectColor($projectId)
    {
        // Generate consistent color per project
        $colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
        ];
        return $colors[$projectId % count($colors)];
    }
}
```

#### 4.3 Calendar View (Day 3-5)
Create `resources/views/calendar/index.blade.php`:

```html
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Calendar') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div id="calendar"></div>
                </div>
            </div>
            
            <!-- Entry Details Modal -->
            <div id="entryModal" class="hidden">
                <!-- Modal content populated by JavaScript -->
            </div>
        </div>
    </div>
    
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    @endpush
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek'
            },
            events: {
                url: '{{ route("calendar.entries") }}',
                method: 'GET',
                extraParams: function() {
                    return {
                        _token: '{{ csrf_token() }}'
                    };
                },
                failure: function() {
                    alert('Error loading time entries');
                }
            },
            eventClick: function(info) {
                showEntryDetails(info.event);
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: false
            },
            displayEventTime: true,
            eventDidMount: function(info) {
                // Add tooltip with duration
                const duration = info.event.extendedProps.duration;
                const hours = Math.floor(duration / 60);
                const minutes = duration % 60;
                info.el.title = `${hours}h ${minutes}m`;
            }
        });
        
        calendar.render();
        
        function showEntryDetails(event) {
            const props = event.extendedProps;
            
            // Create modal content
            const modalHtml = `
                <div class="fixed inset-0 z-50 overflow-y-auto" id="entryModalOverlay">
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal()"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Time Entry Details
                            </h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Client:</span>
                                    <p class="text-gray-900 dark:text-white">${props.clientName}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Project:</span>
                                    <p class="text-gray-900 dark:text-white">${props.projectName}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Description:</span>
                                    <p class="text-gray-900 dark:text-white">${event.title}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Duration:</span>
                                    <p class="text-gray-900 dark:text-white">
                                        ${Math.floor(props.duration / 60)}h ${props.duration % 60}m
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Amount:</span>
                                    <p class="text-gray-900 dark:text-white">$${props.amount.toFixed(2)}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Billable:</span>
                                    <p class="text-gray-900 dark:text-white">
                                        ${props.isBillable ? 'Yes' : 'No'}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end space-x-3">
                                <a href="/time-entries/${event.id}/edit" 
                                   class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    Edit
                                </a>
                                <button onclick="closeModal()" 
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('entryModal').innerHTML = modalHtml;
        }
        
        window.closeModal = function() {
            document.getElementById('entryModal').innerHTML = '';
        };
    });
    </script>
    @endpush
</x-app-layout>
```

#### 4.4 Add Navigation Link (Day 6)
Update `resources/views/layouts/navigation.blade.php`:

```html
<x-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')">
    {{ __('Calendar') }}
</x-nav-link>
```

#### 4.5 Testing (Day 7-8)
- ✅ Calendar loads correctly
- ✅ Events fetch via AJAX
- ✅ Click opens entry details
- ✅ Month/week views work
- ✅ Colors consistent per project
- ✅ Performance with many entries

---

## 📝 Feature 5: Time Entry Templates

**Estimated Time**: 5-7 days  
**Priority**: Medium  
**Complexity**: Medium

### Technical Requirements
- New database table for templates
- CRUD operations for templates
- Quick apply template button
- Save entry as template
- Template management page

### Implementation Steps

#### 5.1 Database Migration (Day 1)
```bash
php artisan make:migration create_time_entry_templates_table
php artisan make:model TimeEntryTemplate -mfc
```

**Migration**:
```php
Schema::create('time_entry_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->string('name'); // Template name (e.g., "Daily standup")
    $table->text('description')->nullable();
    $table->boolean('is_billable')->default(true);
    $table->timestamps();
});
```

**Model** (`app/Models/TimeEntryTemplate.php`):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntryTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'description',
        'is_billable',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

#### 5.2 Controller & Routes (Day 2)
```bash
php artisan make:controller TimeEntryTemplateController --resource
```

**Routes**:
```php
Route::middleware('auth')->group(function () {
    Route::resource('time-entry-templates', TimeEntryTemplateController::class);
    Route::post('/time-entries/from-template/{template}', [TimeEntryController::class, 'fromTemplate'])
        ->name('time-entries.from-template');
});
```

**Controller** (`app/Http/Controllers/TimeEntryTemplateController.php`):
```php
<?php

namespace App\Http\Controllers;

use App\Models\TimeEntryTemplate;
use Illuminate\Http\Request;

class TimeEntryTemplateController extends Controller
{
    public function index()
    {
        $templates = auth()->user()
            ->timeEntryTemplates()
            ->with('project.client')
            ->latest()
            ->get();
            
        return view('time-entry-templates.index', compact('templates'));
    }
    
    public function create()
    {
        $projects = auth()->user()->projects()->with('client')->get();
        return view('time-entry-templates.create', compact('projects'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'description' => 'nullable|string',
            'is_billable' => 'boolean',
        ]);
        
        $template = auth()->user()->timeEntryTemplates()->create($validated);
        
        return redirect()->route('time-entry-templates.index')
            ->with('success', 'Template created successfully.');
    }
    
    public function destroy(TimeEntryTemplate $timeEntryTemplate)
    {
        $this->authorize('delete', $timeEntryTemplate);
        
        $timeEntryTemplate->delete();
        
        return back()->with('success', 'Template deleted successfully.');
    }
}
```

#### 5.3 Template Selection UI (Day 3-4)
Update `resources/views/time-entries/create.blade.php`:

```html
<div class="mb-6" x-data="templateSelector()">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Or start from template:
    </label>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($templates as $template)
        <button type="button" 
                @click="applyTemplate({{ $template->id }})"
                class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 dark:hover:border-indigo-400 transition text-left">
            <div class="font-semibold text-gray-900 dark:text-white">
                {{ $template->name }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $template->project->client->name }} - {{ $template->project->name }}
            </div>
            @if($template->description)
            <div class="text-sm text-gray-600 dark:text-gray-500 mt-1">
                {{ Str::limit($template->description, 50) }}
            </div>
            @endif
        </button>
        @endforeach
    </div>
</div>

<script>
function templateSelector() {
    return {
        applyTemplate(templateId) {
            // Fetch template data
            fetch(`/api/v1/time-entry-templates/${templateId}`)
                .then(response => response.json())
                .then(data => {
                    // Fill form fields
                    document.querySelector('[name="project_id"]').value = data.project_id;
                    document.querySelector('[name="description"]').value = data.description || '';
                    document.querySelector('[name="is_billable"]').checked = data.is_billable;
                    
                    // Trigger change events to update UI
                    document.querySelector('[name="project_id"]').dispatchEvent(new Event('change'));
                });
        }
    }
}
</script>
```

#### 5.4 Save as Template Feature (Day 5)
Add to `resources/views/time-entries/edit.blade.php`:

```html
<form method="POST" action="{{ route('time-entry-templates.store') }}" 
      class="mt-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
    @csrf
    
    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
        Save as Template
    </h3>
    
    <input type="hidden" name="project_id" value="{{ $timeEntry->project_id }}">
    <input type="hidden" name="description" value="{{ $timeEntry->description }}">
    <input type="hidden" name="is_billable" value="{{ $timeEntry->is_billable }}">
    
    <div class="flex items-center space-x-3">
        <input type="text" name="name" placeholder="Template name (e.g., Daily standup)"
               class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
               required>
        <x-primary-button>
            Save Template
        </x-primary-button>
    </div>
</form>
```

#### 5.5 Testing (Day 6-7)
- ✅ Templates create correctly
- ✅ Apply template fills form
- ✅ Save from existing entry works
- ✅ Delete template works
- ✅ Authorization enforced

---

## ✅ Feature 6: Bulk Operations

**Estimated Time**: 5-7 days  
**Priority**: High  
**Complexity**: Medium-High

### Technical Requirements
- Checkbox selection UI
- JavaScript selection manager
- Bulk delete endpoint
- Bulk edit endpoint
- Bulk invoice creation

### Implementation Steps

#### 6.1 Selection UI (Day 1-2)
Update `resources/views/time-entries/index.blade.php`:

```html
<div x-data="bulkSelector()" class="space-y-4">
    <!-- Bulk Actions Toolbar -->
    <div x-show="selectedIds.length > 0" 
         x-transition
         class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-indigo-700 dark:text-indigo-300">
                <span x-text="selectedIds.length"></span> item(s) selected
            </span>
            
            <div class="flex items-center space-x-3">
                <button @click="bulkEdit()" 
                        class="px-3 py-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                    Edit Selected
                </button>
                
                <button @click="bulkInvoice()" 
                        class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                    Create Invoice
                </button>
                
                <button @click="bulkDelete()" 
                        class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                    Delete Selected
                </button>
                
                <button @click="clearSelection()" 
                        class="px-3 py-1 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm">
                    Clear
                </button>
            </div>
        </div>
    </div>
    
    <!-- Table with Checkboxes -->
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead>
            <tr>
                <th scope="col" class="px-6 py-3">
                    <input type="checkbox" 
                           @change="toggleAll($event.target.checked)"
                           class="rounded border-gray-300 text-indigo-600">
                </th>
                <th scope="col" class="px-6 py-3 text-left">Project</th>
                <th scope="col" class="px-6 py-3 text-left">Description</th>
                <th scope="col" class="px-6 py-3 text-left">Duration</th>
                <th scope="col" class="px-6 py-3 text-left">Amount</th>
                <th scope="col" class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($timeEntries as $entry)
            <tr>
                <td class="px-6 py-4">
                    <input type="checkbox" 
                           :checked="selectedIds.includes({{ $entry->id }})"
                           @change="toggleSelection({{ $entry->id }})"
                           class="rounded border-gray-300 text-indigo-600">
                </td>
                <td class="px-6 py-4">
                    {{ $entry->project->name }}
                </td>
                <td class="px-6 py-4">
                    {{ $entry->description }}
                </td>
                <td class="px-6 py-4">
                    {{ floor($entry->duration / 60) }}h {{ $entry->duration % 60 }}m
                </td>
                <td class="px-6 py-4">
                    ${{ number_format($entry->amount, 2) }}
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('time-entries.edit', $entry) }}" 
                       class="text-indigo-600 hover:text-indigo-900">
                        Edit
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
function bulkSelector() {
    return {
        selectedIds: [],
        
        toggleSelection(id) {
            const index = this.selectedIds.indexOf(id);
            if (index === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(index, 1);
            }
        },
        
        toggleAll(checked) {
            if (checked) {
                this.selectedIds = @json($timeEntries->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },
        
        clearSelection() {
            this.selectedIds = [];
        },
        
        bulkDelete() {
            if (!confirm(`Delete ${this.selectedIds.length} time entries?`)) return;
            
            fetch('{{ route("time-entries.bulk-delete") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids: this.selectedIds })
            })
            .then(response => response.json())
            .then(data => {
                window.location.reload();
            });
        },
        
        bulkEdit() {
            // Redirect to bulk edit page with IDs
            const idsParam = this.selectedIds.join(',');
            window.location.href = `/time-entries/bulk-edit?ids=${idsParam}`;
        },
        
        bulkInvoice() {
            // Redirect to invoice creation with pre-selected entries
            const idsParam = this.selectedIds.join(',');
            window.location.href = `/invoices/create?time_entries=${idsParam}`;
        }
    }
}
</script>
```

#### 6.2 Backend Bulk Operations (Day 3-4)
**Routes**:
```php
Route::post('/time-entries/bulk-delete', [TimeEntryController::class, 'bulkDelete'])
    ->name('time-entries.bulk-delete');
Route::get('/time-entries/bulk-edit', [TimeEntryController::class, 'bulkEditForm'])
    ->name('time-entries.bulk-edit');
Route::patch('/time-entries/bulk-update', [TimeEntryController::class, 'bulkUpdate'])
    ->name('time-entries.bulk-update');
```

**Controller Methods**:
```php
public function bulkDelete(Request $request)
{
    $validated = $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'exists:time_entries,id',
    ]);
    
    // Verify ownership of all entries
    $entries = TimeEntry::whereIn('id', $validated['ids'])
        ->where('user_id', auth()->id())
        ->get();
    
    if ($entries->count() !== count($validated['ids'])) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    TimeEntry::whereIn('id', $validated['ids'])->delete();
    
    return response()->json(['success' => true, 'deleted' => count($validated['ids'])]);
}

public function bulkEditForm(Request $request)
{
    $ids = explode(',', $request->input('ids'));
    
    $entries = TimeEntry::whereIn('id', $ids)
        ->where('user_id', auth()->id())
        ->with('project.client')
        ->get();
    
    $projects = auth()->user()->projects()->with('client')->get();
    
    return view('time-entries.bulk-edit', compact('entries', 'projects'));
}

public function bulkUpdate(Request $request)
{
    $validated = $request->validate([
        'ids' => 'required|array',
        'project_id' => 'nullable|exists:projects,id',
        'is_billable' => 'nullable|boolean',
        'hourly_rate' => 'nullable|numeric|min:0',
    ]);
    
    $updateData = collect($validated)->except('ids')->filter()->toArray();
    
    TimeEntry::whereIn('id', $validated['ids'])
        ->where('user_id', auth()->id())
        ->update($updateData);
    
    return redirect()->route('time-entries.index')
        ->with('success', 'Bulk update completed successfully.');
}
```

#### 6.3 Bulk Edit Form (Day 5)
Create `resources/views/time-entries/bulk-edit.blade.php`:

```html
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bulk Edit Time Entries') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Editing {{ count($entries) }} time entries
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Only fill in the fields you want to update. Empty fields will not be changed.
                        </p>
                    </div>
                    
                    <form method="POST" action="{{ route('time-entries.bulk-update') }}">
                        @csrf
                        @method('PATCH')
                        
                        @foreach($entries as $entry)
                        <input type="hidden" name="ids[]" value="{{ $entry->id }}">
                        @endforeach
                        
                        <div class="space-y-4">
                            <!-- Project -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Change Project
                                </label>
                                <select name="project_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">-- Don't change --</option>
                                    @foreach($projects->groupBy('client.name') as $clientName => $clientProjects)
                                    <optgroup label="{{ $clientName }}">
                                        @foreach($clientProjects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Billable Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Billable Status
                                </label>
                                <select name="is_billable" 
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">-- Don't change --</option>
                                    <option value="1">Billable</option>
                                    <option value="0">Non-billable</option>
                                </select>
                            </div>
                            
                            <!-- Hourly Rate Override -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Hourly Rate Override
                                </label>
                                <input type="number" step="0.01" min="0" name="hourly_rate"
                                       placeholder="Leave empty to keep current rates"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <a href="{{ route('time-entries.index') }}" 
                               class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </a>
                            <x-primary-button>
                                Update All Selected
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

#### 6.4 Testing (Day 6-7)
- ✅ Selection UI works correctly
- ✅ Bulk delete removes entries
- ✅ Bulk edit updates correctly
- ✅ Authorization enforced
- ✅ Handles large selections

---

## 📅 Implementation Schedule

### Week 1 (Jan 27 - Jan 31)
- ✅ **Days 1-4**: Dark Mode (complete feature)
- ✅ **Days 5**: Start Keyboard Shortcuts

### Week 2 (Feb 3 - Feb 7)
- ✅ **Days 1-4**: Complete Keyboard Shortcuts
- ✅ **Days 5**: Start Timer Notifications

### Week 3 (Feb 10 - Feb 14)
- ✅ **Days 1-3**: Complete Timer Notifications
- ✅ **Days 4-5**: Start Calendar View

### Week 4 (Feb 17 - Feb 21)
- ✅ **Days 1-5**: Complete Calendar View

### Week 5 (Feb 24 - Feb 28)
- ✅ **Days 1-5**: Time Entry Templates

### Week 6 (Mar 3 - Mar 7)
- ✅ **Days 1-5**: Bulk Operations

### Week 7 (Mar 10 - Mar 14)
- ✅ **Days 1-3**: Testing & bug fixes
- ✅ **Days 4-5**: Documentation & polish

---

## ✅ Definition of Done

For each feature to be considered complete:

1. **Code Complete**
   - ✅ All functionality implemented
   - ✅ Code reviewed and refactored
   - ✅ No console errors or warnings
   - ✅ Works in Chrome, Firefox, Safari

2. **Tests Written**
   - ✅ Unit tests for services
   - ✅ Feature tests for controllers
   - ✅ Browser tests for complex UI (optional)
   - ✅ All tests passing

3. **Documentation**
   - ✅ README updated (if needed)
   - ✅ Inline code comments
   - ✅ User-facing help text
   - ✅ API documentation (if applicable)

4. **User Experience**
   - ✅ Mobile responsive
   - ✅ Dark mode compatible
   - ✅ Accessible (keyboard navigation, screen readers)
   - ✅ Loading states for async operations
   - ✅ Error messages user-friendly

5. **Performance**
   - ✅ No N+1 queries
   - ✅ Page load <2 seconds
   - ✅ Smooth animations (60fps)
   - ✅ Optimized asset sizes

---

## 🎯 Success Metrics

Track these metrics to measure Phase 5 success:

### Usage Metrics
- [ ] **Dark mode adoption**: 40%+ of users enable it
- [ ] **Keyboard shortcut usage**: 15%+ of power users
- [ ] **Calendar views**: 3+ views per user per week
- [ ] **Template usage**: 25%+ of entries created from templates
- [ ] **Bulk operations**: 10%+ of deletions are bulk

### Performance Metrics
- [ ] **Page load time**: <2 seconds (95th percentile)
- [ ] **Time to interactive**: <3 seconds
- [ ] **Calendar render**: <500ms
- [ ] **Notification latency**: <1 second

### Quality Metrics
- [ ] **Test coverage**: >85%
- [ ] **Bug reports**: <5 per feature
- [ ] **User satisfaction**: 4.5+ / 5 stars
- [ ] **Feature adoption**: 60%+ try new features

---

## 🚧 Potential Challenges & Mitigations

### 1. Dark Mode - CSS Complexity
**Challenge**: Maintaining consistent dark mode across all pages  
**Mitigation**: 
- Create comprehensive color system in Tailwind config
- Review all pages systematically
- Use browser DevTools to toggle dark class

### 2. Keyboard Shortcuts - Conflicts
**Challenge**: Shortcuts conflicting with browser/OS defaults  
**Mitigation**:
- Use Ctrl/Cmd modifier for all shortcuts
- Allow shortcuts to be disabled
- Detect and skip when in input fields

### 3. Notifications - Permission Denial
**Challenge**: Users deny notification permissions  
**Mitigation**:
- Graceful degradation (show in-app alerts)
- Clear explanation before requesting
- Settings to re-request permission

### 4. Calendar - Performance with Many Entries
**Challenge**: Slow rendering with 1000+ entries per month  
**Mitigation**:
- Lazy load events (fetch only visible range)
- Implement pagination
- Add loading indicators
- Cache calendar data

### 5. Templates - Data Duplication
**Challenge**: Templates become outdated when projects change  
**Mitigation**:
- Store only template name + references
- Fetch project details on-demand
- Show warning if project deleted

### 6. Bulk Operations - Accidental Deletions
**Challenge**: Users accidentally bulk delete important entries  
**Mitigation**:
- Require confirmation
- Implement "undo" feature (soft deletes)
- Limit bulk operations to 100 items
- Add bulk action audit log

---

## 📦 Dependencies & Prerequisites

### NPM Packages to Install
```bash
npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/interaction
```

### Laravel Packages
None required - using existing stack

### Browser APIs Used
- Notification API (for timer notifications)
- Local Storage (for dark mode preference)
- Web Storage (for keyboard shortcuts state)

### Minimum Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 🔄 Post-Phase Review

After completing Phase 5, conduct a retrospective:

### Review Questions
1. What went well?
2. What could be improved?
3. Were time estimates accurate?
4. Which features had most impact?
5. What should we prioritize next?

### Metrics Review
- Compare planned vs actual timeline
- Analyze user adoption rates
- Review bug counts per feature
- Measure performance improvements

### Documentation Updates
- Update ROADMAP.md with Phase 5 completion
- Create PHASE_5_SUMMARY.md with lessons learned
- Update user documentation
- Record technical decisions in ADRs (Architecture Decision Records)

---

*Plan created: January 26, 2026*  
*Target start: January 27, 2026*  
*Estimated completion: Mid-March 2026*
