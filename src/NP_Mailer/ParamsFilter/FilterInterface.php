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

use Zend\Filter\FilterInterface as ZendFilterInterface;

/**
 * Receives mail parameters that should be sent in order to optionally 
 * filter/inflect them.
 * 
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
interface FilterInterface extends ZendFilterInterface
{
    function setOptions(array $options);
}
