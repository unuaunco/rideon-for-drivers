<?php

/**
 * DriverOweAmountPayment Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    DriverOweAmountPayment
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverOweAmountPayment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */

    use CurrencyConversion;
    
    protected $fillable = ['user_id','transaction_id','amount','currency_code','status'];
    protected $convert_fields = ['amount'];
    public $timestamps = false;

    public function getAmountAttribute(){
        return number_format(($this->attributes['amount']),2); 
    }

    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
