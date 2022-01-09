<?php

/**
 * PaymentMethod Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    PaymentMethod
 * @author      RideOn Team (2020)
 * @version     1.7
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'payment_method';

   
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
