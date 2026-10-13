<?php

namespace App\Modules\Identity;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RecoveryCodes
{
    /**
     * @return list<string>
     */
    public function issue(User $user, int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generate();
            $codes[] = $code;

            RecoveryCode::query()->create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($this->normalize($code)),
            ]);
        }

        return $codes;
    }

    public function consume(User $user, string $code): bool
    {
        $normalized = $this->normalize($code);

        return DB::transaction(function () use ($user, $normalized): bool {
            $stored = RecoveryCode::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->get();

            $match = null;

            foreach ($stored as $recoveryCode) {
                if (Hash::check($normalized, $recoveryCode->code_hash)) {
                    $match = $recoveryCode;
                }
            }

            if ($match === null) {
                return false;
            }

            $match->forceFill(['used_at' => now()])->save();

            return true;
        });
    }

    private function generate(): string
    {
        $raw = bin2hex(random_bytes(8));

        return implode('-', str_split($raw, 4));
    }

    private function normalize(string $code): string
    {
        return strtolower(str_replace(['-', ' '], '', $code));
    }
}
