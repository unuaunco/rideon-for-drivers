<?php
/**
 * Language Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    TollReason
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripTollReason extends Model
{
    
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['trip_id','reason'];
    
}
