<?php

/**
 * Rider Location Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    Rider Location
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderLocation extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'rider_location';

    public $timestamps = false;

    protected $guarded = [];
}