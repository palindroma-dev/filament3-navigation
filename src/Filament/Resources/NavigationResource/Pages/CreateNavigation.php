<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns\HandlesNavigationBuilder;
use RyanChandler\FilamentNavigation\FilamentNavigation;
use Filament\Core\Concerns\CreateRecord\Translatable;

class CreateNavigation extends CreateRecord
{
  use HandlesNavigationBuilder, Translatable;

  public static function getResource(): string
  {
    return FilamentNavigation::get()->getResource();
  }
}
