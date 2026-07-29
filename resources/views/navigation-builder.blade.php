{{--
    Filament 5 wraps a field's view in the field wrapper itself, so this view
    only renders the field body. It used to open with
    `<x-filament-forms::field-wrapper>`, a Filament 3 component whose props
    (`$getHelperText()` and friends) no longer exist.
--}}
<div class="filament-navigation">
    <div wire:key="navigation-items-wrapper">
        <div
            class="space-y-2"
            x-data="navigationSortableContainer({
                statePath: @js($getStatePath())
            })"
            data-sortable-container
        >
            @forelse($getState() as $uuid => $item)
                <x-filament-navigation::nav-item :statePath="$getStatePath() . '.' . $uuid" :item="$item" />
            @empty
                <div @class([
                    'w-full bg-white rounded-lg border border-gray-300 px-3 py-2 text-left',
                    'dark:bg-gray-700 dark:border-gray-600',
                ])>
                    {{__('filament-navigation::filament-navigation.items.empty')}}
                </div>
            @endforelse
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <x-filament::button wire:click="createItem" type="button" size="sm">
            {{__('filament-navigation::filament-navigation.items.add-item')}}
        </x-filament::button>
    </div>
</div>
