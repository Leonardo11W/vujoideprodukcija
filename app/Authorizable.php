<?php

namespace App;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;

trait Authorizable
{
    use AuthorizesRequests;

    /**
     * List of default method names of the Controllers and the related permission.
     */
    private $abilities = [
        'index' => 'view',
        'index_data' => 'view',
        'index_list' => 'view',
        'edit' => 'edit',
        'show' => 'view',
        'update' => 'edit',
        'create' => 'add',
        'store' => 'add',
        'destroy' => 'delete',
        'restore' => 'restore',
        'trashed' => 'restore',
    ];

    /**
     * Override of callAction to perform the authorization before.
     *
     * @return mixed
     */
    public function callAction($method, $parameters)
    {
        if ($ability = $this->getAbility($method)) {
            $this->authorize($ability);
        }

        return parent::callAction($method, $parameters);
    }

    public function getAbility($method)
    {
        $routeName = explode('.', \Request::route()->getName());
        $action = Arr::get($this->getAbilities(), $method);

        if (!$action || !isset($routeName[1])) {
            return null;
        }

        $itemName = str_replace('-', '_', $routeName[1]);
        $singularItem = \Illuminate\Support\Str::singular($itemName);

        $ability = $action . '_' . $itemName;
        $singularAbility = $action . '_' . $singularItem;

        // Admins bypass
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            return $ability;
        }

        // If user has the literal permission (plural), return it
        if (auth()->check() && auth()->user()->can($ability)) {
            return $ability;
        }

        // If user has the singular form permission, return that instead
        if (auth()->check() && $singularItem !== $itemName && auth()->user()->can($singularAbility)) {
            return $singularAbility;
        }

        // If user has neither, return the singular one if it exists in the system (more likely)
        // This makes 403 error messages more accurate for the majority of modules
        return ($singularItem !== $itemName) ? $singularAbility : $ability;
    }

    private function getAbilities()
    {
        return $this->abilities;
    }

    public function setAbilities($abilities)
    {
        $this->abilities = $abilities;
    }
}
