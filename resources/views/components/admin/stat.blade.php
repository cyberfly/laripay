@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'zinc',
])

@php
    $colors = [
        'zinc' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
        'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
        'lime' => 'bg-lime-100 text-lime-600 dark:bg-lime-500/20 dark:text-lime-400',
        'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
        'violet' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400',
    ];
@endphp

<flux:card class="flex items-center gap-4">
    @if ($icon)
        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $colors[$color] ?? $colors['zinc'] }}">
            <flux:icon :name="$icon" class="size-5" />
        </div>
    @endif

    <div class="min-w-0">
        <flux:text size="sm">{{ $label }}</flux:text>
        <flux:heading size="lg" class="truncate">{{ $value }}</flux:heading>
    </div>
</flux:card>
