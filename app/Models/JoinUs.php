<?php

/**
 * Join Us Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    Join Us
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinUs extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'join_us';

    public $timestamps = false;
}
