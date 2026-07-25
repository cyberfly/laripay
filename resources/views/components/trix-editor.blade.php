@props(['wireModel', 'label' => null])

@php($id = 'trix-'.Str::random(8))

<div class="space-y-2" wire:ignore x-data="{ content: @entangle($wireModel) }">
    @if ($label)
        <flux:label :for="$id">{{ $label }}</flux:label>
    @endif

    <input type="hidden" id="{{ $id }}" x-model="content">

    <trix-editor
        input="{{ $id }}"
        x-init="$el.editor.loadHTML(content ?? '')"
        x-on:trix-change="content = $event.target.value"
    ></trix-editor>
</div>
