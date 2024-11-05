<?php

namespace App\Models;

use App\Mail\SendCodeMail;
use App\Models\Continent;
use App\Models\Province;
use App\Models\Region;
use App\Traits\AuditDescription;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
// use Hash;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Mail;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements AuditableContract
{
    // protected $connection = 'mysql2';
    use HasRoles;
    use Auditable;
    use SoftDeletes;
    use AuditDescription;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_no',
        'password',
        'role_id',
        'model',
        'model_id',
        'continent_id',
        'region_id',
        'province_id',
        'user_type',
        'created_by',
        'company_id',
        'is_admin',
        'is_first',
        'member_no'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function createUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function Company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id')->withDefault(['name' => '']);
    }

    public function getDesignation()
    {
        $type = $this->user_type;
        $user = User::find($this->id);
        // dd($type);
        switch ($type) {
            case 'Provincial Accountant':
                // Handle case 1
                $ledgers = Province::find($user->province_id);
                // dd($type);
                if ($ledgers) {
                    return $ledgers->description;
                }
                return "";
                break;

            case 'Regional Accountant':
                // Handle case 2
                // dd($type, $user, $user->regional_id);
                $ledgers = Region::find($user->region_id);
                // dd($type,$user);
                if ($ledgers) {
                    return $ledgers->description;
                }
                return "";
                break;

            case 'Continental Accountant':
                // Handle case 3
                $ledgers = Continent::find($user->continent_id);
                // dd($type);
                if ($ledgers) {
                    return $ledgers->description;
                }
                return "";
                break;

            default:
                // Handle default case or unknown cases
                // dd($type);
                $ledgers = "";
                return $ledgers;
                break;
        }
    }
    public function getUserProvince()
    {
        $type = auth()->user()->user_type;
        $user = auth()->user();
        // dd($type);
        switch ($type) {
            case 'Provincial Accountant':
                // Handle case 1
                $ledgers = Province::find($user->province_id);
                return $ledgers->description ?? "";
                break;

            case 'Regional Accountant':
                // Handle case 2
                // dd($type, $user, $user->regional_id);
                $ledgers = Region::find($user->region_id);
                return $ledgers->description ?? "";
                break;

            case 'Continental Accountant':
                // Handle case 3
                $ledgers = Continent::find($user->continent_id);
                return $ledgers->description ?? "";
                break;

            default:
                // Handle default case or unknown cases
                // dd($type);
                $ledgers = "";
                return $ledgers;
                break;
        }
    }

    public function generateCode()
    {
        $code = rand(1000, 9999);
        // $code =1234;

        UserCode::updateOrCreate(
            ['user_id' => auth()->user()->id],
            ['code' => Hash::make($code)]
        );

        try {

            $details = [
                'title' => 'ACCOUNTING ADMIN OTP',
                'code' => $code,
                'name' => Auth::user()->name
            ];

            // dd('here');
            $email = Auth::user()->email;
            Mail::to($email)->send(new SendCodeMail($details));

        } catch (Exception $e) {
            // info("Error: ". $e->getMessage());
        }
    }

    public function getContinent()
    {
        $continentId = $this->continent_id;
        $continent = Continent::find($continentId);
        $name = $continent->description ?? "";
        return $name;
    }
    public function getRegion()
    {
        $continentId = $this->region_id;
        $continent = Region::find($continentId);
        $name = $continent->description ?? "";
        return $name;
    }
    public function getProvince()
    {
        $continentId = $this->province_id;
        $continent = Province::find($continentId);
        $name = $continent->description ?? "";
        return $name;
    }
   
    public function transformAudit(array $data): array
    {

        $user = $this->getUser(Auth::id());
        $description = $this->generateDescription($data, $user);
        $data['user_id'] = Auth::id();
        $data['description'] = $description;

        return $data;
    }
}
