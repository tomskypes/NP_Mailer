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
