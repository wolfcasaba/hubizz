<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 *
 * All Hubizz service classes should extend this base class
 * for consistent logging and error handling.
 */
abstract class BaseService
{
    /**
     * Log an error message with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($this->getServiceName() . ': ' . $message, $context);
    }

    /**
     * Log an info message with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($this->getServiceName() . ': ' . $message, $context);
    }

    /**
     * Log a warning message with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($this->getServiceName() . ': ' . $message, $context);
    }

    /**
     * Log a debug message with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($this->getServiceName() . ': ' . $message, $context);
    }

    /**
     * Get the service name for logging.
     *
     * @return string
     */
    protected function getServiceName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Handle exceptions with consistent logging.
     *
     * @param \Exception $e
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function handleException(\Exception $e, string $message = '', array $context = []): void
    {
        $this->logError(
            $message ?: 'Exception occurred',
            array_merge($context, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ])
        );
    }

    /**
     * Check if a feature is enabled.
     *
     * @param string $feature
     * @return bool
     */
    protected function isFeatureEnabled(string $feature): bool
    {
        return (bool) config("hubizz.features.{$feature}", false);
    }

    /**
     * Get a Hubizz configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return config("hubizz.{$key}", $default);
    }
}
