<?php

/**
 * Driver Address Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    Driver Address
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverAddress extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'driver_address';

    protected $fillable = ['address_line1', 'address_line2', 'city', 'state', 'postal_code','user_id',];

    public $timestamps = false;


   
}
