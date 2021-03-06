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

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\View\Renderer\RendererInterface as ViewRenderer;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Makes sure that HTML body is assembled the right way. This filter is also 
 * capable of rendering HTML body if it is supplied in form of a ViewModel.
 *
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class HtmlBodyBuilder implements FilterInterface
{
    /**
     * @var \Zend\View\Renderer\RendererInterface 
     */
    protected $viewRenderer = null;
    
    /**
     * Name of a template that should be used as a mail layout, in which every 
     * view model that is to be sent should be embeded in.
     * 
     * @var string 
     */
    protected $layoutTemplate = null;
    
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
        
        $htmlBodyBuilder = new static();
        $htmlBodyBuilder->setViewRenderer(clone $serviceLocator->get('Zend\View\Renderer\PhpRenderer'));
        return $htmlBodyBuilder;
    }
    
    public function setOptions(array $options)
    {
        if (isset($options['layout_template'])) {
            $this->setLayoutTemplate($options['layout_template']);
        }
        
        return $this;
    }
    
    public function setViewRenderer(ViewRenderer $renderer)
    {
        $this->viewRenderer = $renderer;
        return $this;
    }
    
    public function setLayoutTemplate($layoutTemplate)
    {
        $this->layoutTemplate = $layoutTemplate;
        return $this;
    }
    
    public function getLayoutTemplate()
    {
        return $this->layoutTemplate;
    }
    
    public function filter($params)
    {
        $params = $this->processViewParams($params);
        
        if (isset($params['bodyHtml'])) {
            $parts = array();
        
            if (isset($params['bodyText'])) {
                $text = new MimePart($params['bodyText']);
                $text->type = "text/plain";
                $parts[] = $text;
                unset($params['bodyText']);
            }
            
            $html = new MimePart($params['bodyHtml']);
            $html->type = "text/html";
            $parts[] = $html;
            unset($params['bodyHtml']);
            
            $body = new MimeMessage();
            $body->setParts($parts);

            $params['body'] = $body;
        }
        
        return $params;
    }
    
    protected function processViewParams($params)
    {
        if (isset($params['viewTemplate'])) {
            $params['viewModel'] = new ViewModel();
            $params['viewModel']->setTemplate($params['viewTemplate']);
            unset($params['viewTemplate']);
        }

        if (isset($params['viewModel'])) {
            if (!$this->viewRenderer) {
                throw new \DomainException('View renderer must be set in order to render mail view model');
            }  
            
            if (isset($params['translatorTextDomain']) && $this->viewRenderer instanceof \Zend\View\Renderer\PhpRenderer) {
                $this->viewRenderer->plugin('translate')->setTranslatorTextDomain($params['translatorTextDomain']);
            }

            $result = $this->viewRenderer->render($params['viewModel']);

            if ($this->layoutTemplate) {
                $layoutViewModel = new ViewModel();
                $layoutViewModel->setTemplate($this->layoutTemplate)->setVariables(array(
                    'content' => $result
                ));
                $result = $this->viewRenderer->render($layoutViewModel);
            }

            $params['bodyHtml'] = $result;
            unset($params['viewModel']);
        }
        
        return $params;
    }
}
