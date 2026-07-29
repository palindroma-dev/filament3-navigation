<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Actions\EditAction;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\ListNavigations;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\EditNavigation;
use App\Enums\Permissions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RyanChandler\FilamentNavigation\FilamentNavigation;
use RyanChandler\FilamentNavigation\Models\Navigation;
use Filament\Core\Concerns\Resource\Translatable;

class NavigationResource extends Resource
{
  use Translatable;

  protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';

  protected static bool $showTimestamps = true;

  private static ?string $workNavigationLabel = null;

  private static ?string $workPluralLabel = null;

  private static ?string $workLabel = null;

  public static function disableTimestamps(bool $condition = true): void
  {
    static::$showTimestamps = !$condition;
  }

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')->schema([
          TextInput::make('name')
            ->label(__('filament-navigation::filament-navigation.attributes.name'))
            ->reactive()
            ->debounce()
            ->afterStateUpdated(function (?string $state, Set $set) {
              if (!$state) {
                return;
              }

              $set('handle', Str::slug($state));
            })
            ->required(),
          ViewField::make('items')
            ->label(__('filament-navigation::filament-navigation.attributes.items'))
            ->default([])
            ->view('filament-navigation::navigation-builder'),
        ])
          ->columnSpan([
            12,
            'lg' => 8,
          ]),
        Group::make([
          Section::make('')->schema([
            TextInput::make('handle')
              ->label(__('filament-navigation::filament-navigation.attributes.handle'))
              ->required()
              ->unique(column: 'handle', ignoreRecord: true),
            View::make('filament-navigation::card-divider')
              ->visible(static::$showTimestamps),
            // Filament 5 moved this off `Table::$defaultDateTimeDisplayFormat`
            // (a public static in v3) onto the schema/table instance.
            Placeholder::make('created_at')
              ->label(__('filament-navigation::filament-navigation.attributes.created_at'))
              ->visible(static::$showTimestamps)
              ->content(fn(?Navigation $record) => $record ? $record->created_at->translatedFormat($schema->getDefaultDateTimeDisplayFormat()) : new HtmlString('&mdash;')),
            Placeholder::make('updated_at')
              ->label(__('filament-navigation::filament-navigation.attributes.updated_at'))
              ->visible(static::$showTimestamps)
              ->content(fn(?Navigation $record) => $record ? $record->updated_at->translatedFormat($schema->getDefaultDateTimeDisplayFormat()) : new HtmlString('&mdash;')),
          ]),
        ])
          ->columnSpan([
            12,
            'lg' => 4,
          ]),
      ])
      ->columns(12);
  }

  public static function navigationLabel(?string $string): void
  {
    self::$workNavigationLabel = $string;
  }

  public static function pluralLabel(?string $string): void
  {
    self::$workPluralLabel = $string;
  }

  public static function label(?string $string): void
  {
    self::$workLabel = $string;
  }

  public static function getNavigationLabel(): string
  {
    return self::$workNavigationLabel ?? parent::getNavigationLabel();
  }

  public static function getModelLabel(): string
  {
    return self::$workLabel ?? parent::getModelLabel();
  }

  public static function getPluralModelLabel(): string
  {
    return self::$workPluralLabel ?? parent::getPluralModelLabel();
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label(__('filament-navigation::filament-navigation.attributes.name'))
          ->searchable(),
        TextColumn::make('handle')
          ->label(__('filament-navigation::filament-navigation.attributes.handle'))
          ->searchable(),
        TextColumn::make('created_at')
          ->label(__('filament-navigation::filament-navigation.attributes.created_at'))
          ->dateTime()
          ->sortable(),
        TextColumn::make('updated_at')
          ->label(__('filament-navigation::filament-navigation.attributes.updated_at'))
          ->dateTime()
          ->sortable(),
      ])
      ->recordActions([
        EditAction::make()
          ->icon(null),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ListNavigations::route('/'),
      'edit' => EditNavigation::route('/{record}'),
    ];
  }

  public static function getModel(): string
  {
    return FilamentNavigation::get()->getModel();
  }

  public static function shouldRegisterNavigation(): bool
  {
    return false;
  }
}
