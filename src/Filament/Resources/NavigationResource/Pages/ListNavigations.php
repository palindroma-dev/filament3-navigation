<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use RyanChandler\FilamentNavigation\FilamentNavigation;

class ListNavigations extends ListRecords
{
  use Translatable;

  public static function getResource(): string
  {
    return FilamentNavigation::get()->getResource();
  }

  protected function getActions(): array
  {
    return [];
  }
}
