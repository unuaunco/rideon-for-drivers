<?php

/**
 * Payment Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    Payment
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'payment';

    protected $fillable = ['trip_id', 'correlation_id', 'admin_transaction_id','driver_transaction_id','driver_payout_status','admin_payout_status'];

    public $timestamps = false;
}
