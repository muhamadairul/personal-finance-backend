<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="mb-6">
        <nav class="flex space-x-1 rounded-xl bg-gray-100 dark:bg-gray-800 p-1" aria-label="Tabs">
            <button wire:click="$set('activeTab', 'financial')" @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 transition-all duration-200',
                'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow' =>
                    $this->activeTab === 'financial',
                'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/60 hover:text-gray-700 dark:hover:text-gray-300' =>
                    $this->activeTab !== 'financial',
            ])>
                <span class="flex items-center justify-center gap-2">
                    <x-heroicon-o-banknotes class="h-5 w-5" />
                    <span class="hidden sm:inline">Financial Overview</span>
                    <span class="sm:hidden">💰</span>
                </span>
            </button>

            <button wire:click="$set('activeTab', 'engagement')" @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 transition-all duration-200',
                'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow' =>
                    $this->activeTab === 'engagement',
                'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/60 hover:text-gray-700 dark:hover:text-gray-300' =>
                    $this->activeTab !== 'engagement',
            ])>
                <span class="flex items-center justify-center gap-2">
                    <x-heroicon-o-user-group class="h-5 w-5" />
                    <span class="hidden sm:inline">User Engagement</span>
                    <span class="sm:hidden">👥</span>
                </span>
            </button>

            <button wire:click="$set('activeTab', 'api_health')" @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 transition-all duration-200',
                'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow' =>
                    $this->activeTab === 'api_health',
                'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/60 hover:text-gray-700 dark:hover:text-gray-300' =>
                    $this->activeTab !== 'api_health',
            ])>
                <span class="flex items-center justify-center gap-2">
                    <x-heroicon-o-cpu-chip class="h-5 w-5" />
                    <span class="hidden sm:inline">API & System Health</span>
                    <span class="sm:hidden">🔧</span>
                </span>
            </button>

            <button wire:click="$set('activeTab', 'maintenance')" @class([
                'flex-1 rounded-lg px-4 py-2.5 text-sm font-medium leading-5 transition-all duration-200',
                'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow' =>
                    $this->activeTab === 'maintenance',
                'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/60 hover:text-gray-700 dark:hover:text-gray-300' =>
                    $this->activeTab !== 'maintenance',
            ])>
                <span class="flex items-center justify-center gap-2">
                    <x-heroicon-o-wrench-screwdriver class="h-5 w-5" />
                    <span class="hidden sm:inline">Export & Maintenance</span>
                    <span class="sm:hidden">📦</span>
                </span>
            </button>
        </nav>
    </div>

    {{-- Widget Area --}}
    <x-filament-widgets::widgets :columns="$this->getColumns()" :widgets="$this->getVisibleWidgets()" />
</x-filament-panels::page>
