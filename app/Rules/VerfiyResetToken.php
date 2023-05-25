<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class VerfiyResetToken implements InvokableRule
{
    protected  $email;

    /**
     * @param string $email
     */

    public function __construct(string|null $email)
    {
        $this->email = $email;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        try {
            if (is_null($this->email)) {
                $fail("The given email address is invalid");
            }    
            $user = DB::table('password_resets')->where('email', $this->email)->first();
            if (is_null($user)) {
                throw new ModelNotFoundException();
            }
            if (!Hash::check($value, $user->token)) {
                $fail("The entered token is invalid");
            }
        } catch (ModelNotFoundException $modelNotFoundException) {
            Log::error($modelNotFoundException->getMessage());
            $fail("Could not reset password. Please check your token or email address.");
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            $fail("An unexpected error occurred. Please try again later.");
        }
    }
}
