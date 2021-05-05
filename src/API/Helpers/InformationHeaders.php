<?php

declare(strict_types=1);

namespace Auth0\SDK\API\Helpers;

/**
 * Class InformationHeaders
 * Builds, extends, modifies, and formats SDK telemetry data.
 */
class InformationHeaders
{
    /**
     * Default header data to send.
     */
    protected array $data = [];

    /**
     * Set the main SDK name and version.
     *
     * @param string $name    SDK name.
     * @param string $version SDK version number.
     */
    public function setPackage(
        string $name,
        string $version
    ): void {
        $this->data['name'] = $name;
        $this->data['version'] = $version;
    }

    /**
     * Set the main SDK name and version to the PHP SDK.
     */
    public function setCorePackage(): void
    {
        $this->setPackage('auth0-php', ApiClient::API_VERSION);
        $this->setEnvProperty('php', phpversion());
    }

    /**
     * Add an optional env property for SDK telemetry.
     *
     * @param string $name    Property name to set, name of dependency or platform.
     * @param string $version Version number of dependency or platform.
     */
    public function setEnvProperty(
        string $name,
        string $version
    ): void {
        if (! isset($this->data['env']) || ! is_array($this->data['env'])) {
            $this->data['env'] = [];
        }

        $this->data['env'][$name] = $version;
    }

    /**
     * Replace the current env data with new data.
     *
     * @param array $data Env data to add.
     */
    public function setEnvironmentData(
        array $data
    ): void {
        $this->data['env'] = $data;
    }

    /**
     * Get the current header data as an array.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->data;
    }

    /**
     * Return a header-formatted string.
     */
    public function build(): string
    {
        return base64_encode(json_encode($this->get()));
    }

    /**
     * Extend an existing InformationHeaders object.
     * Used in dependant modules to set a new SDK name and version but keep existing PHP SDK data.
     *
     * @param InformationHeaders $headers InformationHeaders object to extend.
     */
    public static function extend(
        InformationHeaders $headers
    ): InformationHeaders {
        $new_headers = new InformationHeaders();
        $old_headers = $headers->get();

        if (isset($old_headers['env']) && count($old_headers['env'])) {
            $new_headers->setEnvironmentData($old_headers['env']);
        }

        $new_headers->setEnvProperty($old_headers['name'], $old_headers['version']);

        return $new_headers;
    }
}
