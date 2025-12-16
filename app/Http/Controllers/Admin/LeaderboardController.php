<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pronostic;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Classement général (top 100)
        $leaderboard = $this->getLeaderboard();

        // Classement par village
        $villages = Village::where('is_active', true)->get();
        $villageLeaderboards = [];
        foreach ($villages as $village) {
            $villageLeaderboards[$village->id] = $this->getLeaderboard($village->id, 10);
        }

        return view('admin.leaderboard.index', compact('leaderboard', 'villages', 'villageLeaderboards'));
    }

    public function village($villageId)
    {
        $village = Village::findOrFail($villageId);
        $leaderboard = $this->getLeaderboard($villageId, 50);

        return view('admin.leaderboard.village', compact('village', 'leaderboard'));
    }

    /**
     * Calculer le classement
     */
    protected function getLeaderboard($villageId = null, $limit = 100)
    {
        $query = User::select('users.*')
            ->selectRaw('COALESCE(SUM(pronostics.points_won), 0) as total_points')
            ->selectRaw('COUNT(pronostics.id) as total_pronostics')
            ->selectRaw('SUM(CASE WHEN pronostics.is_winner = 1 THEN 1 ELSE 0 END) as total_wins')
            ->leftJoin('pronostics', 'users.id', '=', 'pronostics.user_id')
            ->where('users.is_active', true)
            ->groupBy('users.id');

        if ($villageId) {
            $query->where('users.village_id', $villageId);
        }

        return $query->orderByDesc('total_points')
            ->orderByDesc('total_wins')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir le badge d'un utilisateur selon ses points
     */
    public static function getBadge($points)
    {
        if ($points >= 100) return ['name' => 'Champion', 'icon' => '👑', 'color' => 'text-yellow-500'];
        if ($points >= 60) return ['name' => 'Or', 'icon' => '🥇', 'color' => 'text-yellow-600'];
        if ($points >= 30) return ['name' => 'Argent', 'icon' => '🥈', 'color' => 'text-gray-400'];
        if ($points >= 10) return ['name' => 'Bronze', 'icon' => '🥉', 'color' => 'text-orange-600'];
        return ['name' => 'Débutant', 'icon' => '🌱', 'color' => 'text-green-500'];
    }
}
