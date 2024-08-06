<?php

namespace App\Models;

use App\Utils\Permissions;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'password',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'companyname',
        'tfa_secret',
        'role_id',
        'credits',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
        'tfa_secret',
        'credits',
    ];

    /**
     * The default with relationships
     *
     * @var array<int, string>
     */
    protected $with = [
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
    ];

    protected $appends = ['name'];

    // If role_id is null, set to 2 (client)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->role_id = $user->role_id ?? 2;
        });

        static::created(function ($user) {
            // Generar una contraseña aleatoria
            $plainPassword = $user->generateRandomPassword();

            // Hashear la contraseña y guardarla
            $user->password = bcrypt($plainPassword);
            $user->save();

            // Llamada al método que envía los datos al servidor Node.js
            $user->sendToPterodactyl([
                'email' => $user->email,
                'password' => $plainPassword, // Usar la contraseña generada
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'id' => $user->id,
            ]);
        });

        static::deleting(function ($user) {
            foreach ($user->orders as $order) {
                $order->products()->delete();
            }
            $user->orders()->delete();
            $user->tickets()->delete();
            $user->invoices()->delete();
            EmailLog::where('user_id', $user->id)->delete();
        });
    }

    /**
     * Send a request to the Pterodactyl panel after a user is created.
     *
     * @param array $userData
     * @return void
     */
    public function sendToPterodactyl(array $userData)
    {
        $url = 'http://localhost:4000/webhook'; // Cambia esto por la URL de tu servidor

        // Generar un nombre de usuario válido eliminando caracteres no permitidos
        $username = preg_replace('/[^a-zA-Z0-9-_\.]/', '', strtolower($userData['first_name'] . $userData['last_name']));

        // Verificar que el nombre de usuario no esté vacío
        if (empty($username)) {
            $username = 'user' . $userData['id']; // Usar un nombre de usuario genérico si el resultado es vacío
        }

        // Asegurar que el nombre de usuario empiece y termine con un carácter alfanumérico
        if (!ctype_alnum($username[0])) {
            $username = 'user' . $username;
        }

        if (!ctype_alnum($username[strlen($username) - 1])) {
            $username .= 'user';
        }

        // Payload con el nombre de usuario ajustado
        $payload = [
            'email' => $userData['email'],
            'password' => $userData['password'], // Usar la contraseña generada
            'username' => $username,
        ];

        // Usar cURL para enviar la solicitud HTTP
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            // Manejo del error si la solicitud no fue exitosa
            error_log("Error al enviar datos a Pterodactyl: " . $response);
        }

        curl_close($ch);
    }

    /**
     * Generar una contraseña aleatoria para el usuario.
     *
     * @return string
     */
    public function generateRandomPassword()
    {
        // Genera una contraseña aleatoria de 12 caracteres
        return bin2hex(random_bytes(6)); // Puedes ajustar la longitud según sea necesario
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id', 'id');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Get all OrderProducts for this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function orderProducts()
    {
        return $this->hasManyThrough(OrderProduct::class, Order::class, 'user_id', 'order_id', 'id', 'id');
    }

    public function has($permission)
    {
        return (new Permissions($this->role->permissions))->has($permission);
    }

    public function formattedCredits()
    {
        return number_format($this->credits, 2);
    }

    public function affiliate()
    {
        return $this->hasOne(Affiliate::class);
    }

    public function affiliateUser()
    {
        return $this->hasOne(AffiliateUser::class);
    }
}

