<?php

/**
 * Company Docuemnts Model
 *
 * @package     RideOnForDrivers
 * @subpackage  Model
 * @category    Company Docuemnts
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDocuments extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'company_documents';

    public $timestamps = false;

    protected $fillable = ['company_id','license_photo','license_exp_date','insurance_photo','insurance_exp_date'];
   
}
