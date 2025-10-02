<?php

namespace Lkn\WcCieloPaymentGateway\Services;

/**
 * Service Container for dependency injection
 *
 * @since 1.25.0
 */
class ServiceContainer
{
    /**
     * Container for registered services
     *
     * @var array
     */
    private $services = [];

    /**
     * Container for service instances
     *
     * @var array
     */
    private $instances = [];

    /**
     * Register a service in the container
     *
     * @param string $name Service name
     * @param callable|object $service Service definition or instance
     * @param bool $singleton Whether to treat as singleton
     */
    public function register(string $name, $service, bool $singleton = true): void
    {
        $this->services[$name] = [
            'service' => $service,
            'singleton' => $singleton
        ];
    }

    /**
     * Get a service from the container
     *
     * @param string $name Service name
     * @return mixed Service instance
     * @throws \InvalidArgumentException If service is not registered
     */
    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new \InvalidArgumentException("Service '{$name}' is not registered.");
        }

        $service = $this->services[$name];

        // Return singleton instance if already created
        if ($service['singleton'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Create instance
        $instance = $this->createInstance($service['service']);

        // Store singleton instance
        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a service is registered
     *
     * @param string $name Service name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Create service instance
     *
     * @param callable|object $service
     * @return mixed
     */
    private function createInstance($service)
    {
        if (is_callable($service)) {
            return $service($this);
        }

        return $service;
    }
}
