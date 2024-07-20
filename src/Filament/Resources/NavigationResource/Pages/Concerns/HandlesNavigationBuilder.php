<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns;

use App\Filament\Actions\Forms\LocaleSwitcher;
use App\Filament\Actions\Forms\ModalLocaleSwitcher;
use Filament\Actions\Action;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RyanChandler\FilamentNavigation\FilamentNavigation;
use Z3d0X\FilamentFabricator\Models\Page;
use Filament\Core\Services\GoogleTranslateService;

trait HandlesNavigationBuilder
{
  public $mountedItem;

  public $mountedItemData = [];

  public $mountedChildTarget;

  public function sortNavigation(string $targetStatePath, array $targetItemsStatePaths)
  {
    $items = [];

    foreach ($targetItemsStatePaths as $targetItemStatePath) {
      $item = data_get($this, $targetItemStatePath);
      $uuid = Str::afterLast($targetItemStatePath, '.');

      $items[$uuid] = $item;
    }

    data_set($this, $targetStatePath, $items);
  }

  public function addChild(string $statePath)
  {
    $this->mountedChildTarget = $statePath;

    $this->mountAction('item');
  }

  public function removeItem(string $statePath)
  {
    $uuid = Str::afterLast($statePath, '.');

    $parentPath = Str::beforeLast($statePath, '.');
    $parent = data_get($this, $parentPath);

    data_set($this, $parentPath, Arr::except($parent, $uuid));
  }

  public function editItem(string $statePath)
  {
    $this->mountedItem = $statePath;
    $this->mountedItemData = Arr::except(data_get($this, $statePath), 'children');

    $this->mountAction('item');
  }

  public function createItem()
  {
    $this->mountedItem = null;
    $this->mountedItemData = [];
    $this->mountedActionData = [];

    $this->mountAction('item');
  }

  protected function getActions(): array
  {
    return [
      LocaleSwitcher::make(),
      Action::make('item')
        ->mountUsing(function (ComponentContainer $form) {
          if (!$this->mountedItem) {
            return;
          }

          $activeLocale = $this->getLocale();
          $this->mountedItemData['label'] = $this->mountedItemData['label'][$activeLocale] ?? $this->mountedItemData['label'];

          $form->fill($this->mountedItemData);
        })
        ->view('filament-navigation::hidden-action')
        ->form([
          /*ModalLocaleSwitcher::make('lang_switcher')
            ->label(__('filament-navigation::filament-navigation.attributes.items'))
            ->view('filament.actions.modal-locale-action'),*/
          TextInput::make('label')
            ->label(__('filament-navigation::filament-navigation.items-modal.label'))
            ->required(),
          Select::make('type')
            ->label(__('filament-navigation::filament-navigation.items-modal.type'))
            ->options(function () {
              $types = FilamentNavigation::get()->getItemTypes();

              return array_combine(array_keys($types), Arr::pluck($types, 'name'));
            })
            ->afterStateUpdated(function ($state, Select $component): void {
              if (!$state) {
                return;
              }

              // NOTE: This chunk of code is a workaround for Livewire not letting
              //       you entangle to non-existent array keys, which wire:model
              //       would normally let you do.
              $component
                ->getContainer()
                ->getComponent(fn(Component $component) => $component instanceof Group)
                ->getChildComponentContainer()
                ->fill();
            })
            ->reactive(),
          Group::make()
            ->statePath('data')
            ->whenTruthy('type')
            ->schema(function (Get $get) {
              $type = $get('type');

              return FilamentNavigation::get()->getItemTypes()[$type]['fields'] ?? [];
            }),
          Group::make()
            ->statePath('data')
            ->visible(fn(Component $component) => $component->evaluate(FilamentNavigation::get()->getExtraFields()) !== [])
            ->schema(function (Component $component) {
              return FilamentNavigation::get()->getExtraFields();
            }),
        ])
        ->modalWidth('md')
        ->action(function (array $data) {
          $translateService = app(GoogleTranslateService::class);

          if (isset($data["data"]["page_id"])) {
            $page = Page::findOrFail($data["data"]["page_id"]);
            $data["data"]["page_slug"] = $page->slug;
          }

          $activeLocale = $this->getLocale();
          $existingData = $this->mountedItem ? data_get($this, $this->mountedItem) : null;
          $label = $data['label'];
          $data['label'] = $existingData['label'] ?? [];

          foreach(config('app.locales') as $locale) {
            if(empty($data['label'][$locale]) || $locale == $activeLocale) {
              if($locale == $activeLocale) {
                $data['label'][$locale] = $label;
              } else {
                $data['label'][$locale] = $translateService->translate($label, $locale);
              }
            }
          }

          if ($this->mountedItem) {
            data_set($this, $this->mountedItem, array_merge(data_get($this, $this->mountedItem), $data));

            $this->mountedItem = null;
            $this->mountedItemData = [];
          } elseif ($this->mountedChildTarget) {
            $children = data_get($this, $this->mountedChildTarget . '.children', []);

            $children[(string)Str::uuid()] = [
              ...$data,
              ...['children' => []],
            ];

            data_set($this, $this->mountedChildTarget . '.children', $children);

            $this->mountedChildTarget = null;
          } else {
            $this->data['items'][(string)Str::uuid()] = [
              ...$data,
              ...['children' => []],
            ];
          }

          $this->mountedActionData = [];
        })
        ->modalButton(__('filament-navigation::filament-navigation.items-modal.btn'))
        ->label(__('filament-navigation::filament-navigation.items-modal.title')),
    ];
  }

  private static function getLocale()
  {
    return session()->get('filament.translatable.activeLocale') ?? config('app.fallback_locale');
  }
}
