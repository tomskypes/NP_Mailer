<?php
/*
 * NP_Mailer
 * Copyright (C) 2008-2013  Nikola Posa
 * 
 * This program is free software: you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License 
 * along with this program.If not, see <http://www.gnu.org/licenses/>.
 */

namespace NP_Mailer;

use Zend\Mail\Transport\TransportInterface as Transport;
use Zend\Mail\Message;
use Zend\Filter\FilterChain;
use NP_Mailer\ParamsFilter\FilterInterface as ParamsFilter;

/**
 * Facilitates sending of email messages.

 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class Mailer
{
    /**
     * @var \Zend\Mail\Transport\TransportInterface 
     */
    protected $transport;
    
    /**
     * Ready-to-send email configurations.
     * 
     * @var array 
     */
    protected $configs = array();

    /**
     * @var FilterChain 
     */
    protected $paramsFilters;
    
    /**
     * Default values for mail parameters.
     * 
     * @var array 
     */
    protected $defaults = array();

    protected static $translatableParams = array(
        'subject',
    );

    public function __construct(Transport $transport, array $configs = array())
    {
        $this->setTransport($transport);
        $this->addConfigs($configs);
    }
    
    public function setTransport(Transport $transport)
    {
        $this->transport = $transport;
        return $this;
    }
    
    public function getTransport()
    {
        return $this->transport;
    }

    public function addConfigs(array $configs)
    {
        foreach ($configs as $name => $params) {
            $this->addConfig($name, $params);
        }
        
        return $this;
    }
    
    public function addConfig($name, array $params)
    {
        $this->configs[$name] = $params;
        return $this;
    }
    
    public function getConfigs()
    {
        return $this->configs;
    }

    public function setParamsFilters(FilterChain $filters)
    {
        $this->paramsFilters = $filters;
        return $this;
    }

    public function getParamsFilters()
    {
        if (!$this->paramsFilters) {
            $this->paramsFilters = new FilterChain();
        }
        
        return $this->paramsFilters;
    }
    
    public function addParamsFilter(ParamsFilter $filter)
    {
        $this->getParamsFilters()->attach($filter);
        return $this;
    }
    
    public function setDefaults(array $params)
    {
        $this->defaults = $params;
        return $this;
    }
    
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param array $params
     * @param string $mailConfigName OPTIONAL Name of a mail configuration.
     * @return \Zend\Mail\Message
     */
    public function send(array $params, $mailConfigName = null)
    {
        $message = $this->buildMessage($params, $mailConfigName);

        $this->transport->send($message);
        
        return $message;
    }
    
    /**
     * @param array $params
     * @param string $mailConfigName OPTIONAL Name of a mail configuration.
     * @return \Zend\Mail\Message
     */
    public function buildMessage(array $params, $mailConfigName = null)
    {
        $message = new Message();
        
        $mailConfig = array();
        if ($mailConfigName) {
            if (!isset($this->configs[$mailConfigName])) {
                throw new \DomainException("Mail configuration with name '$mailConfigName' was not found");
            }
            
            $mailConfig = $this->configs[$mailConfigName];
        }
        
        $params = array_replace_recursive($this->defaults, $mailConfig, $params);
        $params = $this->getParamsFilters()->filter($params);
        
        foreach ($params as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($message, $method)) {
                $message->$method($value);
            }
        }
        
        return $message;
    }
}
