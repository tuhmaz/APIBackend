<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'type' => trim((string) $request->get('type', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $activities = $this->getActivities(1, $filters);
        return view('content.dashboard.activities.index', compact('activities', 'filters'));
    }

    public function loadMore(Request $request)
    {
        $page = $request->input('page', 1);
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'type' => trim((string) $request->get('type', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];
        $activities = $this->getActivities($page, $filters);
        
        return response()->json([
            'html' => view('content.dashboard.activities._list', compact('activities'))->render(),
            'hasMore' => count($activities) === 20
        ]);
    }

    private function getActivities($page, array $filters = [])
    {
        // Obtener actividades reales de la base de datos usando el modelo Activity
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $query = Activity::with('causer');

        // Date filter: default to last 24 hours if none provided
        $dateFrom = isset($filters['date_from']) && !empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : Carbon::now()->subHours(24);
        $dateTo = isset($filters['date_to']) && !empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : Carbon::now();

        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Type filter maps to subject type class basename
        if (!empty($filters['type'])) {
            $type = strtolower($filters['type']);
            $query->where(function ($q) use ($type) {
                // Match by class basename anywhere in subject_type
                $q->whereRaw('LOWER(subject_type) LIKE ?', ["%$type%"]) 
                  ->orWhereNull('subject_type');
            });
        }

        // Search in description and properties
        if (!empty($filters['q'])) {
            $qStr = $filters['q'];
            $query->where(function ($q) use ($qStr) {
                $q->where('description', 'like', "%$qStr%")
                  ->orWhere('properties', 'like', "%$qStr%");
            });
        }

        $activities = $query
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->map(function ($activity) {
                // Mapear los datos del modelo Activity al formato esperado por la vista
                $iconMap = [
                    'created' => 'bx-plus-circle',
                    'updated' => 'bx-edit',
                    'deleted' => 'bx-trash',
                    'login' => 'bx-log-in',
                    'logout' => 'bx-log-out',
                    'default' => 'bx-activity'
                ];
                
                $typeMap = [
                    'article' => 'article',
                    'news' => 'news',
                    'comment' => 'comment',
                    'user' => 'user',
                    'default' => 'system'
                ];
                
                // Determinar el tipo basado en el log_name o subject_type
                $type = 'default';
                if (isset($activity->subject_type)) {
                    $subjectClass = strtolower(class_basename($activity->subject_type));
                    $type = array_key_exists($subjectClass, $typeMap) ? $subjectClass : 'default';
                }
                
                // Determinar el icono basado en la descripción
                $icon = $iconMap['default'];
                foreach ($iconMap as $key => $value) {
                    if (strpos(strtolower($activity->description), $key) !== false) {
                        $icon = $value;
                        break;
                    }
                }
                
                // Obtener información del usuario والصورة الشخصية
                $userName = $activity->causer?->name ?? 'Sistema';
                $userAvatar = $activity->causer?->profile_photo_url ?? asset('assets/img/avatars/default.png');
                
                // Construir una descripción legible
                $description = $activity->description;
                if ($activity->subject) {
                    $subjectName = '';
                    if (method_exists($activity->subject, 'getNameAttribute')) {
                        $subjectName = $activity->subject->name;
                    } elseif (isset($activity->subject->title)) {
                        $subjectName = $activity->subject->title;
                    } elseif (isset($activity->subject->name)) {
                        $subjectName = $activity->subject->name;
                    } elseif (isset($activity->properties['attributes']['title'])) {
                        $subjectName = $activity->properties['attributes']['title'];
                    } elseif (isset($activity->properties['attributes']['name'])) {
                        $subjectName = $activity->properties['attributes']['name'];
                    }
                    
                    if (!empty($subjectName)) {
                        $description .= ' "' . $subjectName . '"';
                    }
                }
                
                return [
                    'type' => $type,
                    'icon' => $icon,
                    'action' => ucfirst($activity->description),
                    'description' => $description,
                    'time' => $activity->created_at,
                    'user' => $userName,
                    'user_avatar' => $userAvatar,
                    'user_profile_url' => $activity->causer ? route('front.members.show', $activity->causer->id) : null,
                    'properties' => $activity->properties
                ];
            });
        
        return $activities;
    }
    
    // Método para limpiar actividades antiguas (puede ser llamado por un programador de tareas)
    public function cleanOldActivities()
    {
        $last24Hours = Carbon::now()->subHours(24);
        $deleted = Activity::where('created_at', '<', $last24Hours)->delete();
        
        return response()->json([
            'success' => true,
            'message' => "Se han eliminado $deleted registros de actividad antiguos"
        ]);
    }
}
