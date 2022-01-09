<?php

/**
 * Image Handler Interface
 *
 * @package     RideOnForDrivers
 * @subpackage  Contracts
 * @category    Image Handler
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
*/

namespace App\Contracts;

interface ImageHandlerInterface
{
	public function upload($image, $options);
	public function delete($image);
	public function getImage($file_name, $options);
}