<?php

/**
 * ReferrelReward Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    ReferrelReward
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferrelReward extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'referrel_rewards';

    public $timestamps = true;
}
