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
