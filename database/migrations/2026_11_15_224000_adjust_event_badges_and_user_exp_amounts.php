<?php

use App\Models\Member;
use App\Models\MemberBadge;
use App\Services\GamificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('member_badges') || ! Schema::hasTable('users')) {
            return;
        }

        $gamification = app(GamificationService::class);

        DB::transaction(function () use ($gamification) {
            $badges = MemberBadge::all();

            foreach ($badges as $badge) {
                $newExp = $gamification->calculateEventMemberExp($badge->jabatan);
                $oldExp = (int) $badge->exp_earned;
                $diff = $newExp - $oldExp;

                if ($diff !== 0) {
                    $badge->update(['exp_earned' => $newExp]);

                    $user = Member::find($badge->user_id);
                    if ($user) {
                        $user->update([
                            'exp' => max(0, ((int) $user->exp) + $diff),
                        ]);
                        cache()->forget("portal_stats_{$user->id}");
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for exp data adjustment
    }
};
