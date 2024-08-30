<?php

namespace RyanChandler\FilamentNavigation\Policies;

use App\Enums\Permissions;
use Filament\Core\Models\AdminPanelUser;

class NavigationPolicy
{
  public function viewAny(AdminPanelUser $adminPanelUser): bool
  {
    return $this->checkPermissions($adminPanelUser);
  }

  public function create(AdminPanelUser $adminPanelUser): bool
  {
    return $this->checkPermissions($adminPanelUser);
  }

  public function view(AdminPanelUser $adminPanelUser): bool
  {
    return $this->checkPermissions($adminPanelUser);
  }

  public function update(AdminPanelUser $adminPanelUser): bool
  {
    return $this->checkPermissions($adminPanelUser);
  }

  public function delete(AdminPanelUser $adminPanelUser): bool
  {
    return $this->checkPermissions($adminPanelUser);
  }

  private function checkPermissions(AdminPanelUser $adminPanelUser): bool
  {
    return $adminPanelUser->can(Permissions::ManageNavigation->value);
  }
}