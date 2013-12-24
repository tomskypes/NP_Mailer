<?php
/**
 * This file is part of the NP_Mailer package.
 * 
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 * 
 * For full copyright and license information, please refer to the LICENSE file, 
 * located at the package root folder.
 */

namespace NP_Mailer\ParamsFilter;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\Translator as ZendTranslator;

/**
 * Capable of translating certain mail params.
 *
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class Translator implements FilterInterface, TranslatorAwareInterface
{
    protected $translatableParams = array(
        'subject',
    );
    
    /**
     * @var ZendTranslator
     */
    protected $translator = null;

    /**
     * @var bool
     */
    protected $translatorEnabled = true;

    /**
     * @var string
     */
    protected $translatorTextDomain = 'default';
    
    public function __construct(array $translatableParams = array())
    {
        if (!empty($translatableParams)) {
            $this->setTranslatableParams($translatableParams);
        }
    }
    
    /**
     * Default factory.
     * 
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return NP_Mailer\ParamsFilter\Translator
     */
    public static function factory(ServiceLocatorInterface $serviceLocator)
    {
        if ($serviceLocator instanceof \Zend\ServiceManager\AbstractPluginManager) {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }
        
        $instance = new static();
        $instance->setTranslator($serviceLocator->get('Translator'));
        return $instance;
    }
    
    public function setOptions(array $options)
    {
        if (isset($options['translatable_params'])) {
            $this->setTranslatableParams($options['translatable_params']);
        }
        
        return $this;
    }
    
    public function setTranslatableParams(array $translatableParams)
    {
        $this->translatableParams = $translatableParams;
    } 
    
    public function getTranslatableParams()
    {
        return $this->translatableParams;
    }

    public function filter($params)
    {
        if ($this->isTranslatorEnabled() && ($translator = $this->getTranslator())) {
            foreach ($params as $key => $value) {
                if ($key == 'translatorTextDomain') {
                    $this->setTranslatorTextDomain($value);
                } elseif (in_array($key, $this->translatableParams)) {
                    $params[$key] = $translator->translate($value, $this->getTranslatorTextDomain());
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Sets translator to use in helper
     *
     * @param ZendTranslator $translator
     * @param string $textDomain
     * @return mixed
     */
    public function setTranslator(ZendTranslator $translator = null, $textDomain = null)
    {
        $this->translator = $translator;

        if (!is_null($textDomain)) {
            $this->setTranslatorTextDomain($textDomain);
        }

        return $this;
    }

    /**
     * Returns translator used in object
     *
     * @return ZendTranslator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Checks if the object has a translator
     *
     * @return bool
     */
    public function hasTranslator()
    {
        return !is_null($this->translator);
    }

    /**
     * Sets whether translator is enabled and should be used
     *
     * @param bool $enabled
     * @return mixed
     */
    public function setTranslatorEnabled($enabled = true)
    {
        $this->translatorEnabled = $enabled;

        return $this;
    }

    /**
     * Returns whether translator is enabled and should be used
     *
     * @return bool
     */
    public function isTranslatorEnabled()
    {
        return $this->translatorEnabled;
    }

    /**
     * Set translation text domain
     *
     * @param string $textDomain
     * @return mixed
     */
    public function setTranslatorTextDomain($textDomain = 'default')
    {
        $this->translatorTextDomain = $textDomain;

        return $this;
    }

    /**
     * Return the translation text domain
     *
     * @return string
     */
    public function getTranslatorTextDomain()
    {
        return $this->translatorTextDomain;
    }
}
