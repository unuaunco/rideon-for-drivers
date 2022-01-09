<?php

/**
 * ApiCredential Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    ApiCredential
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiCredentials extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'api_credentials';

    public $timestamps = false;
}
