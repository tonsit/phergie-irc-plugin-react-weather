<?php

/**
 * Apixu provider for the Weather plugin for Phergie
 *
 * @link https://github.com/chrismou/phergie-irc-plugin-react-weather for the canonical source repository
 * @copyright Copyright (c) 2016 Chris Chrisostomou (https://mou.me)
 * @license http://phergie.org/license New BSD License
 * @package Chrismou\Phergie\Plugin\Weather
 */

namespace Chrismou\Phergie\Plugin\Weather\Provider;

use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;

class Apixu implements WeatherProviderInterface
{
    /**
     * @var string
     */
    protected $apiUrl = 'http://api.apixu.com/v1/current.json';

    /**
     * @var string
     */
    protected $appId = "";

    public function __construct(array $config = [])
    {
        if (isset($config['appId'])) {
            $this->appId = $config['appId'];
        }
    }

    /**
     * Return the url for the API request
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     *
     * @return string
     */
    public function getApiRequestUrl(Event $event)
    {
        $params = $event->getCustomParams();
        $query = trim(implode(" ", $params));

        $querystringParams = [
            'q' => $query,
            'key' => $this->appId
        ];

        return sprintf("%s?%s", $this->apiUrl, http_build_query($querystringParams));
    }

    /**
     * Validate the provided parameters
     * The plugin requires at least one parameter (in most cases, this will be a location string)
     *
     * @param array $params
     *
     * @return boolean
     */
    public function validateParams(array $params)
    {
        return (count($params)) ? true : false;
    }

    /**
     * Returns an array of lines to send back to IRC when the http request is successful
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiResponse
     *
     * @return array
     */
    public function getSuccessLines(Event $event, $apiResponse)
    {
        $data = json_decode($apiResponse);
        if (isset($data->location) && isset($data->location->name)) {
            return [
                sprintf(
                    "%s, %s, %s | %s | Temp: %dC %dF | Humidity: %s%% | Wind: %s @ %d mph %d kph | Clouds: %s",
                    $data->location->name,
                    $data->location->region,
                    $data->location->country,
                    $data->current->condition->text,
                    $data->current->temp_c,
                    $data->current->temp_f,
                    $data->current->humidity,
                    $data->current->wind_dir,
                    $data->current->wind_mph,
                    $data->current->wind_kph,
                    $data->current->cloud
                )
            ];
        } else {
            return $this->getNoResultsLines($event, $apiResponse);
        }
    }

    /**
     * Return an array of lines to send back to IRC when there are no results
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiResponse
     *
     * @return array
     */
    public function getNoResultsLines(Event $event, $apiResponse)
    {
        return ['No weather data found for this location'];
    }

    /**
     * Return an array of lines to send back to IRC when the request fails
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param string $apiError
     *
     * @return array
     */
    public function getRejectLines(Event $event, $apiError)
    {
        return ['Something went wrong... ಠ_ಠ'];
    }

    /**
     * Returns an array of lines for the help response
     *
     * @return array
     */
    public function getHelpLines()
    {
        return [
            'Usage: weather [place] [country]',
            '[place] - address, town, city, zip code, etc. Can be multiple words',
            'Instructs the bot to query Apixu for weather info for the specified location'
        ];
    }
}
