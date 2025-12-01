<?php

namespace RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource\Pages\Concerns;

// use Filament\Core\Actions\Forms\LocaleSwitcher; // Removed - using translation tabs instead
use Filament\Core\Actions\Forms\ModalLocaleSwitcher;
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
// use Filament\Core\Services\GoogleTranslateService; // Removed - not using automatic translation

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
      // LocaleSwitcher::make(), // Removed - using translation tabs in menu items instead
      Action::make('item')
        ->mountUsing(function (ComponentContainer $form) {
          if (!$this->mountedItem) {
            return;
          }

          $activeLocale = $this->getLocale();
          $labelData = $this->mountedItemData['label'] ?? [];
          $data = $this->mountedItemData['data'] ?? [];

          // If label is an array (translations), populate the translation tabs
          if (is_array($labelData)) {
            // Ensure data structure exists
            if (!isset($this->mountedItemData['data'])) {
              $this->mountedItemData['data'] = [];
            }

            foreach (config('app.locales') as $locale) {
              // Initialize locale data structure if it doesn't exist
              if (!isset($this->mountedItemData['data'][$locale])) {
                $this->mountedItemData['data'][$locale] = [];
              }

              // Populate title from label array
              if (isset($labelData[$locale])) {
                $this->mountedItemData['data'][$locale]['title'] = $labelData[$locale];
              }
            }
          } elseif (is_string($labelData) && !empty($labelData)) {
            // Old format - single string label, populate for all locales
            if (!isset($this->mountedItemData['data'])) {
              $this->mountedItemData['data'] = [];
            }

            foreach (config('app.locales') as $locale) {
              if (!isset($this->mountedItemData['data'][$locale])) {
                $this->mountedItemData['data'][$locale] = [];
              }

              // If we don't have a title for this locale, use the label string
              if (!isset($this->mountedItemData['data'][$locale]['title'])) {
                $this->mountedItemData['data'][$locale]['title'] = $labelData;
              }
            }
          }

          // Preserve existing data fields (url, target, image, etc.)
          if (isset($data['url'])) {
            $this->mountedItemData['data']['url'] = $data['url'];
          }
          if (isset($data['target'])) {
            $this->mountedItemData['data']['target'] = $data['target'];
          }
          if (isset($data['image'])) {
            $this->mountedItemData['data']['image'] = $data['image'];
          }

          $form->fill($this->mountedItemData);
        })
        ->view('filament-navigation::hidden-action')
        ->form([
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
            ->live()
            ->required(),
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
          // Removed Google Translate API calls - using translation tabs instead
          // $translateService = app(GoogleTranslateService::class);

          if (isset($data["data"]["page_id"])) {
            $page = Page::findOrFail($data["data"]["page_id"]);
            $data["data"]["page_slug"] = $page->slug;
          }

          // Handle translations from the translation tabs (extraFields)
          $activeLocale = $this->getLocale();
          $existingData = $this->mountedItem ? data_get($this, $this->mountedItem) : null;

          // Build label array - start with existing labels if available
          $labelArray = [];
          if (isset($existingData['label']) && is_array($existingData['label'])) {
            $labelArray = $existingData['label'];
          }

          // Get translations from the translation tabs (in data[locale][title])
          if (isset($data['data']) && is_array($data['data'])) {
            foreach (config('app.locales') as $locale) {
              if (isset($data['data'][$locale]['title']) && !empty($data['data'][$locale]['title'])) {
                $labelArray[$locale] = $data['data'][$locale]['title'];
              } elseif (!isset($labelArray[$locale]) && isset($existingData['data'][$locale]['title'])) {
                // Preserve existing translation if not updated
                $labelArray[$locale] = $existingData['data'][$locale]['title'];
              }
            }

            // Preserve non-translation data fields (url, target, image, etc.)
            // These should not be in the locale arrays
            $nonLocaleFields = ['url', 'target', 'image', 'page_id', 'page_slug'];
            foreach ($nonLocaleFields as $field) {
              if (isset($data['data'][$field])) {
                // Keep it at the data level, not in locale arrays
                $data['data'][$field] = $data['data'][$field];
              } elseif (isset($existingData['data'][$field])) {
                // Preserve existing value
                $data['data'][$field] = $existingData['data'][$field];
              }
            }

            // Clean up: remove locale keys that are not in config
            foreach (array_keys($data['data']) as $key) {
              if (!in_array($key, config('app.locales')) && !in_array($key, $nonLocaleFields)) {
                // This might be a nested structure, preserve it
                if (!isset($data['data'][$key]) || !is_array($data['data'][$key])) {
                  unset($data['data'][$key]);
                }
              }
            }
          }

          // Ensure we have at least one translation
          if (empty($labelArray)) {
            // Fallback: use first available locale
            $fallbackLocale = config('app.fallback_locale', 'en');
            $labelArray[$fallbackLocale] = 'New Item';
          }

          // Set the label as an array of translations
          $data['label'] = $labelArray;

          // Ensure children is always an array
          if (!isset($data['children']) || $data['children'] === null || !is_array($data['children'])) {
            $data['children'] = [];
          }

          if ($this->mountedItem) {
            // Merge with existing data, preserving children structure
            $existingItem = data_get($this, $this->mountedItem);
            if (isset($existingItem['children']) && is_array($existingItem['children'])) {
              $data['children'] = $existingItem['children'];
            }

            data_set($this, $this->mountedItem, array_merge($existingItem ?? [], $data));

            $this->mountedItem = null;
            $this->mountedItemData = [];
          } elseif ($this->mountedChildTarget) {
            $children = data_get($this, $this->mountedChildTarget . '.children', []);

            // Ensure children is an array
            if (!is_array($children)) {
              $children = [];
            }

            $children[(string)Str::uuid()] = [
              ...$data,
              'children' => [],
            ];

            data_set($this, $this->mountedChildTarget . '.children', $children);

            $this->mountedChildTarget = null;
          } else {
            // Ensure items is initialized
            if (!isset($this->data['items']) || !is_array($this->data['items'])) {
              $this->data['items'] = [];
            }

            $this->data['items'][(string)Str::uuid()] = [
              ...$data,
              'children' => [],
            ];
          }

          $this->mountedActionData = [];
        })
        ->modalButton(__('filament-navigation::filament-navigation.items-modal.btn'))
        ->label(__('filament-navigation::filament-navigation.items-modal.title')),
    ];
  }

  private function getLocale()
  {
    return session()->get('filament.translatable.activeLocale') ?? config('app.fallback_locale');
  }

  public function getActiveLocaleProperty()
  {
    return $this->getLocale();
  }
}

