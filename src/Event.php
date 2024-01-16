<?php

namespace Viezel\Amplitude;

/**
 * Event object, used to make a consistent interface and serialization of the event JSON used in Amplitude API calls
 *
 * To maintain better parity with the official Amplitude HTTP API, can set the built-in properties using underscored
 * name (e.g. user_id instead of userId), though camelcase is recommended for better looking code.
 *
 * @property string $userId
 * @property string $deviceId
 * @property string $eventType
 * @property int $time
 * @property array $eventProperties Any values set that do not match built-in property will be set in this array
 * @property array $userProperties
 * @property string $appVersion
 * @property string $platform
 * @property string $osName
 * @property string $osVersion
 * @property string $deviceBrand
 * @property string $deviceManufacturer
 * @property string $deviceModel
 * @property string $deviceType
 * @property string $carrier
 * @property string $country
 * @property string $region
 * @property string $city
 * @property string $dma
 * @property string $language
 * @property float $price
 * @property int $quantity
 * @property float $revenue
 * @property string $productId
 * @property string $revenueType
 * @property float $locationLat
 * @property float $locationLng
 * @property string $ip
 * @property string $idfa
 * @property string $adid
 */
class Event implements \JsonSerializable
{
    /**
     * Array of data for this event
     */
    protected array $data = [];

    /**
     * Array of built-in properties used for events, and the data type for each one.
     *
     * The name used here is what is expected by the Amplitude HTTP API, and how the data will be stored internally,
     * however these can be set/retrieved using camelcase.
     */
    protected array $availableVars = [
        'user_id' => 'string',
        'device_id' => 'string',
        'event_type' => 'string',
        'time' => 'int',
        'event_properties' => 'array',
        'user_properties' => 'array',
        'groups' => 'array',
        'group_properties' => 'array',
        'app_version' => 'string',
        'platform' => 'string',
        'os_name' => 'string',
        'os_version' => 'string',
        'device_brand' => 'string',
        'device_manufacturer' => 'string',
        'device_model' => 'string',
        'carrier' => 'string',
        'country' => 'string',
        'region' => 'string',
        'city' => 'string',
        'dma' => 'string',
        'language' => 'string',
        'price' => 'float',
        'quantity' => 'int',
        'revenue' => 'float',
        'productId' => 'string',
        'revenueType' => 'string',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'ip' => 'string',
        'idfa' => 'string',
        'idfv' => 'string',
        'adid' => 'string',
        'android_id' => 'string',
        'event_id' => 'int',
        'session_id' => 'string',
        'insert_id' => 'string',
        'plan' => 'array',
    ];

    /**
     * Constructor
     *
     * @param  array  $data Initial data to set on the event
     */
    public function __construct(array $data = [])
    {
        if (! empty($data)) {
            $this->setProperties($data);
        }
    }

    /**
     * Set the user properties on the event
     */
    public function setUserProperties(array $userProperties): self
    {
        $props = $this->userProperties ?: [];
        $this->userProperties = array_merge($props, $userProperties);

        return $this;
    }

    /**
     * Set a value in the event.
     *
     * If the name matches one of the built-in event properties, such as user_id, device_id, etc. OR matches the camel
     * case version like userId, deviceId etc. - it will set the built-in property, casting the value to the
     * appropriate type for that property
     *
     * If the name does not match either underscore or camelCase version of a built-in event property name, it will
     * set the value in the event_properties array.
     *
     * It also accepts an array of key => value pairs for the first argument, to pass in an array of properties to set.
     *
     * Note that only built-in event properties are normalized to match the built-in name.  Custom properties that get
     * set in event_properties are not normalized.  Meaning if you use a camelcase name, name with spaces in it, etc,
     * it will use that name as-is without attempting to normalize.
     *
     * @param  string  $name If is array, will set key:value pairs
     * @param  mixed  $value
     */
    public function set(string $name, $value): self
    {
        $name = $this->normalize($name);
        if (! isset($this->availableVars[$name])) {
            // treat it like an event_property
            $this->data['event_properties'][$name] = $value;

            return $this;
        }

        switch ($this->availableVars[$name]) {
            case 'int':
                $value = (int) $value;
                break;
            case 'float':
                $value = (float) $value;
                break;
            case 'array':
                $value = (array) $value;
                break;
            default:
                $value = (string) $value;
                break;
        }
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @see set()
     */
    public function setProperties(array $properties): self
    {
        foreach ($properties as $key => $val) {
            $this->set($key, $val);
        }

        return $this;
    }

    /**
     * Gets the event property, either from built-in event properties or the custom properties from event_properties.
     *
     * Method is case-sensitive for custom properties. Built-in event properties can use camelcase OR underscore, either
     * one will work.
     *
     * If no value found, returns null.
     *
     *
     * @return mixed|null
     */
    public function get(string $name)
    {
        $name = $this->normalize($name);
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } elseif (isset($this->data['event_properties'][$name])) {
            return $this->data['event_properties'][$name];
        }

        return null;
    }

    /**
     * Unset event property, either from built-in event properties or the custom properties from event_properties.
     *
     * Method is case-sensitive for custom properties. Built-in event properties can use camelcase OR underscore, either
     * one will work.
     */
    public function unsetProperty(string $name): self
    {
        $name = $this->normalize($name);
        if (isset($this->availableVars[$name])) {
            unset($this->data[$name]);
        } elseif (isset($this->data['event_properties'])) {
            unset($this->data['event_properties'][$name]);
        }

        return $this;
    }

    /**
     * Check if event property is set, either from built-in event properties or custom properties from event_properties
     *
     * Method is case-sensitive for custom properties. Built-in event properties can use camelcase OR underscore, either
     * one will work.
     */
    public function isPropertySet(string $name): bool
    {
        $name = $this->normalize($name);

        return isset($this->data[$name]) || isset($this->data['event_properties'][$name]);
    }

    /**
     * Magic method to set the value.
     *
     * @see set()
     *
     * @param  mixed  $value
     */
    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Magic method to get the value
     *
     * @see get()
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Unset event property
     *
     * See the unsetProperty() method
     */
    public function __unset(string $name)
    {
        $this->unsetProperty($name);
    }

    /**
     * Magic method to see if name is set
     *
     * Uses same normalization on the name as the set method, where it will match built-in properties for either
     * camelcase or snake_case version of property
     *
     * @return bool
     */
    public function __isset(string $name)
    {
        return $this->isPropertySet($name);
    }

    /**
     * Normalized the name, by attempting to camelcase / underscore it to see if it matches any built-in property names.
     *
     * If it matches a built-in property name, will return the normalized property name. Returns the name
     * un-modified otherwise.
     */
    protected function normalize(string $name): string
    {
        if (isset($this->availableVars[$name])) {
            return $name;
        }
        if (preg_match('/^[a-zA-Z_]+$/', $name)) {
            // No spaces or unexpected vars, this could be camelCased version or underscore version of a built-in
            // var name, check to see if it matches
            $underscore = Inflector::underscore($name);
            if (isset($this->availableVars[$underscore])) {
                return $underscore;
            }
            // In case it is one of the camel-cased versions
            $camel = Inflector::camelCase($name);
            if (isset($this->availableVars[$camel])) {
                return $camel;
            }
        }

        // Could not find name, just use original un-altered, probably used in event_properties
        return $name;
    }

    /**
     * Convert the event to array format
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * JSON serialize
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
