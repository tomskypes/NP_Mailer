<?php
/**
 * This file is part of the NP_Mailer package.
 * 
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 * 
 * For full copyright and license information, please refer to the LICENSE file, 
 * located at the package root folder.
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
