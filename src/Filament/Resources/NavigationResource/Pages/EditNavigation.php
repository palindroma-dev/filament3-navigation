<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns\HandlesNavigationBuilder;
use RyanChandler\FilamentNavigation\FilamentNavigation;
use Filament\Core\Concerns\EditRecord\Translatable;

class EditNavigation extends EditRecord
{
    use HandlesNavigationBuilder, Translatable;

    public static function getResource(): string
    {
        return FilamentNavigation::get()->getResource();
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Initialize activeLocale if not set
        if (!isset($this->activeLocale) || $this->activeLocale === null) {
            $this->activeLocale = static::getResource()::getDefaultTranslatableLocale();
        }
    }

    protected function fillForm(): void
    {
        // Call parent to fill form with record data
        parent::fillForm();
        
        // Ensure activeLocale is set (fallback if not set in mount)
        if (!isset($this->activeLocale) || $this->activeLocale === null) {
            $this->activeLocale = static::getResource()::getDefaultTranslatableLocale();
        }
        
        // Ensure items are loaded into the form data
        if ($this->record && $this->record->exists) {
            $items = $this->record->items ?? [];
            // Set items in the form data so ViewField can access it
            $this->data['items'] = $items;
        }
    }
}
