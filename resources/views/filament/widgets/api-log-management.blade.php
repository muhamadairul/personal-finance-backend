<x-filament-widgets::widget>
    <x-filament::section heading="Manajemen API Logs" icon="heroicon-o-cog-6-tooth" description="Aksi maintenance untuk tabel api_logs">
        <div class="flex flex-wrap gap-3">
            {{ $this->clearOldLogsAction }}
            {{ $this->clearAllLogsAction }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
