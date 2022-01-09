<?php
/**
 * Language Model
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

class Language extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'language';

    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
