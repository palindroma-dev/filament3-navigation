<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns\HandlesNavigationBuilder;
use RyanChandler\FilamentNavigation\FilamentNavigation;
use App\Concerns\EditRecord\Translatable;

class EditNavigation extends EditRecord
{
    use HandlesNavigationBuilder, Translatable;

    public static function getResource(): string
    {
        return FilamentNavigation::get()->getResource();
    }
}
