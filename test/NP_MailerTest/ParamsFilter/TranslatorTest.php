<?php
/**
 * This file is part of the NP_Mailer package.
 * 
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 * 
 * For full copyright and license information, please refer to the LICENSE file, 
 * located at the package root folder.
 */

namespace NP_MailerTest\ParamsFilter;

use PHPUnit_Framework_TestCase;
use NP_Mailer\ParamsFilter\Translator as ParamsTranslator;

/**
 * @group mailer
 * @group mailer_params_filter
 * 
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class TranslatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ParamsTranslator 
     */
    protected $paramsTranslator;
    
    protected $translator;

    protected function setUp()
    {
        $this->paramsTranslator = new ParamsTranslator();
        $this->translator = $this->getMockBuilder('Zend\I18n\Translator\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paramsTranslator->setTranslator($this->translator);
    }
    
    protected function translate($translations)
    {
        $i = 0;
        foreach ($translations as $orig => $translated) {
            $this->translator->expects($this->at($i++))
                ->method('translate')
                ->with($orig)
                ->will($this->returnValue($translated));
        }
    }

    public function testSettingTranslatableParams()
    {
        $translatable = array('subject', 'message');
        
        $this->paramsTranslator->setTranslatableParams($translatable);
        
        $this->assertEquals($translatable, $this->paramsTranslator->getTranslatableParams());
    }
    
    public function testSettingTranslatableParamsThroughOptions()
    {
        $translatable = array('message');
        
        $this->paramsTranslator->setOptions(array(
            'translatable_params' => $translatable,
        ));
        
        $this->assertEquals($translatable, $this->paramsTranslator->getTranslatableParams());
    }
    
    public function testFilteringOnlyTranslatableParams()
    {
        $translatable = array('subject');
        $translations = array(
            'test' => 'test123',
        );
        $params = array(
            'subject' => 'test',
            'from' => 'foo@bar.com',
            'to' => 'test@test.com',
        );
        
        $this->translate($translations);
        
        $this->paramsTranslator->setTranslatableParams($translatable);
        $filtered = $this->paramsTranslator->filter($params);
        
        $this->assertEquals($filtered['subject'], $translations[$params['subject']]);
        $this->assertEquals($filtered['from'], $params['from']); //Unaltered
    }
    
    public function testFilteringNotPerformedIfTranslatorIsDisabled()
    {
        $this->translator->expects($this->never())->method('translate');
                
        $this->paramsTranslator->setTranslatorEnabled(false);
        $this->paramsTranslator->filter(array(
            'subject' => 'test',
        ));
    }
    
    public function testTranslatorTextDomainSetFromParams()
    {
        $translatorTextDomain = 'test';
        $params = array(
            'translatorTextDomain' => $translatorTextDomain,
        );
        
        $this->paramsTranslator->filter($params);
        
        $this->assertEquals($translatorTextDomain, $this->paramsTranslator->getTranslatorTextDomain());
    }
}
