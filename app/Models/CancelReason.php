<?php
/**
 * Language Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    CancelReason
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelReason extends Model
{
    
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['reason','status','cancelled_by'];

    public function scopeActive($query) {
        return $query->where('status', 'Active');
    }
    
}
