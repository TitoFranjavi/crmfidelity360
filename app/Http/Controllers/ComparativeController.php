<?php

namespace App\Http\Controllers;

use App\Http\Models\Comparative;
use App\Http\Models\User;
use App\Http\Models\Enterprise;
use App\Helpers\UserHelper;
use Illuminate\Http\Request;

class ComparativeController extends Controller
{
   public function index(Request $request)
    {
        $user = auth()->user();

        $visibleUserIds = $this->resolveVisibleUserIds($user);

        $query = Comparative::whereIn('createdBy', $visibleUserIds);

        if ($request->filled('user_id')) {
            $filterUserId = (string) $request->get('user_id');

            if (!in_array($filterUserId, $visibleUserIds, true)) {
                return response()->json([
                    'error' => 'No tienes permiso para ver comparativas de este usuario'
                ], 403);
            }

            $query->where('createdBy', $filterUserId);
        }

        $comparatives = $query
            ->orderBy('createdAt', 'desc')
            ->get();

        $creatorIds = $comparatives
            ->pluck('createdBy')
            ->filter()
            ->map(function ($id) {
                return (string) $id;
            })
            ->unique()
            ->values()
            ->toArray();

        $usersById = User::whereIn('_id', $creatorIds)
    ->get([
        '_id',
        'firstName',
        'lastName',
        'firstname',
        'lastname',
        'first_name',
        'last_name',
        'name',
        'surname'
    ])
        ->keyBy(function ($item) {
            return (string) $item['_id'];
        });

        return $comparatives->map(function ($comparative) use ($usersById) {
            $data = $comparative->toArray();

            // hasContract se persiste al guardar un contrato con el mismo CUPS (ver OrderService).
            $data['hasContract'] = (bool) ($data['hasContract'] ?? false);

            $creatorId = isset($data['createdBy']) ? (string) $data['createdBy'] : null;

            if ($creatorId && isset($usersById[$creatorId])) {
                $creator = $usersById[$creatorId];

                $data['createdByUser'] = [
                    '_id' => (string) $creator['_id'],
                    'firstName' => $creator['firstName'] ?? $creator['firstname'] ?? $creator['first_name'] ?? $creator['name'] ?? '',
                    'lastName' => $creator['lastName'] ?? $creator['lastname'] ?? $creator['last_name'] ?? $creator['surname'] ?? '',
                ];
            }

            return $data;
        })->values();
    }

    /**
     * Resuelve los IDs de usuarios cuyas comparativas puede ver el usuario logueado.
     * Con el permiso "users.admiWhiHier" (administrar sin jerarquía) abarca TODOS
     * los usuarios de su subdominio; en caso contrario, solo su rama jerárquica.
     */
    private function resolveVisibleUserIds($user): array
    {
        $userId = (string) $user['_id'];

        $userSubdomain = UserHelper::getUserSubdomain($userId);

        if (!$userSubdomain) {
            return $this->getVisibleUserIds($userId);
        }

        $label = $user['label'] ?? null;
        $usersPermissions = $userSubdomain['labels_permissions'][$label]['users'] ?? null;

        $canViewWholeSubdomain = is_array($usersPermissions)
            && in_array('admiWhiHier', $usersPermissions, true);

        if ($canViewWholeSubdomain) {
            $subdomainUsers = UserHelper::getSubdomainUserList($userSubdomain);

            return array_values(array_map(
                fn($subUser) => is_array($subUser) ? (string) $subUser['_id'] : (string) $subUser,
                $subdomainUsers
            ));
        }

        return $this->getVisibleUserIds($userId);
    }

    private function getVisibleUserIds($userId)
    {
        $visibleUserIds = [(string) $userId];
        $pendingUserIds = [(string) $userId];

        while (!empty($pendingUserIds)) {
            $currentUserId = array_shift($pendingUserIds);

            $children = User::where('responsibles', $currentUserId)->get(['_id']);

            foreach ($children as $child) {
                $childId = (string) $child['_id'];

                if (!in_array($childId, $visibleUserIds, true)) {
                    $visibleUserIds[] = $childId;
                    $pendingUserIds[] = $childId;
                }
            }
        }

        return $visibleUserIds;
    }

    public function store(Request $request){

        $comparative = json_decode($request['comparative'], true);

        try{
            $id = Comparative::insertGetId($comparative, '_id');

            return response()->json([
                '_id' => (string) $id,
                'message' => 'La comparativa ha sido creada correctamente'
            ]);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }

    public function update(Request $request){
        $comparative = json_decode($request['comparative'], true);

        try{
            Comparative::updateOrCreate(['_id' => $comparative['_id']], $comparative);
            return response()->json(['message' => 'La comparativa ha sido creada correctamente']);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id){
        Comparative::destroy($id);
        return response()->json(['message' => 'La comparativa ha sido eliminada correctamente']);
    }

    public function countValidBillComparative(Request $request)
    {
        $userSubdomain = $request['userSubdomain'];

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain)->first();

        if (!$enterprise || empty($enterprise->subscription)) {
            return response()->json([
                'limit' => 'No tienes una suscripción activa.'
            ], 400);
        }

        $subscription = $enterprise->subscription;

        $includedScans = data_get($subscription, 'included.scans');
        $usedScans = (int) data_get($subscription, 'usage.scans', 0);
        $extraScansRemaining = (int) data_get($subscription, 'extras.one_time.scans.remaining', 0);

        $source = null;

        // Plan ilimitado: cuenta uso, pero no limita
        if ($includedScans === null) {
            data_set($subscription, 'usage.scans', $usedScans + 1);
            $source = 'unlimited';
        }

        // Plan normal: consume escaneos incluidos
        elseif ($usedScans < (int) $includedScans) {
            data_set($subscription, 'usage.scans', $usedScans + 1);
            $source = 'plan';
        }

        // Extras puntuales
        elseif ($extraScansRemaining > 0) {
            data_set($subscription, 'extras.one_time.scans.remaining', $extraScansRemaining - 1);
            $source = 'extra';
        }

        // Sin escaneos disponibles
        else {
            return response()->json([
                'limit' => 'No hay escaneos disponibles. Has alcanzado el límite de tu plan y no tienes escaneos extra.'
            ], 400);
        }

        $enterprise->subscription = $subscription;
        $enterprise->save();

        return response()->json([
            'message' => 'Se ha contabilizado correctamente la comparativa',
            'source' => $source,
            'usage_scans' => data_get($subscription, 'usage.scans', 0),
            'extra_scans_remaining' => data_get($subscription, 'extras.one_time.scans.remaining', 0),
        ]);
    }
}
