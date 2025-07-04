<?php

namespace LaravelStytch\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;

trait HasStytchOrganization
{
    /**
     * Get the Stytch organization ID column name.
     */
    public function getStytchOrganizationIdColumn(): string
    {
        return config('stytch.stytch_organization_id_column', 'stytch_organization_id');
    }

    /**
     * Get the Stytch organization name column name.
     */
    public function getStytchOrganizationNameColumn(): string
    {
        return config('stytch.stytch_organization_name_column', 'name');
    }

    /**
     * Get the Stytch organization ID.
     */
    public function getStytchOrganizationId(): ?string
    {
        $column = $this->getStytchOrganizationIdColumn();
        return $this->{$column};
    }

    /**
     * Set the Stytch organization ID.
     */
    public function setStytchOrganizationId(string $stytchOrganizationId): void
    {
        $column = $this->getStytchOrganizationIdColumn();
        $this->{$column} = $stytchOrganizationId;
    }

    /**
     * Get the organization's Stytch name.
     */
    public function getStytchOrganizationName(): ?string
    {
        $column = $this->getStytchOrganizationNameColumn();
        return $this->{$column};
    }

    /**
     * Set the organization's Stytch name.
     */
    public function setStytchOrganizationName(string $name): void
    {
        $column = $this->getStytchOrganizationNameColumn();
        $this->{$column} = $name;
    }

    /**
     * Scope to find an organization by Stytch organization ID.
     */
    #[Scope]
    public function stytchOrganizationId(Builder $query, string $stytchOrganizationId): void
    {
        $column = $this->getStytchOrganizationIdColumn();
        $query->where($column, $stytchOrganizationId);
    }

    /**
     * Scope to find an organization by Stytch organization name.
     */
    #[Scope]
    public function stytchOrganizationName(Builder $query, string $name): void
    {
        $column = $this->getStytchOrganizationNameColumn();
        $query->where($column, $name);
    }
}
