<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class WorkerMonitorController extends Controller
{
    public function index()
    {
        try {
            $data = [
                'workerStatus' => $this->getWorkerStatus(),
                'systemInfo' => $this->getSystemInfo(),
                'recentLogs' => $this->getRecentLogs()
            ];
            
            Log::info('WorkerMonitorController: Dati recuperati', [
                'queue_size' => $data['systemInfo']['queue_size']
            ]);

            return view('worker-monitor.index', $data);
        } catch (\Exception $e) {
            Log::error('Errore nel WorkerMonitorController: ' . $e->getMessage());
            return view('worker-monitor.index', [
                'workerStatus' => [],
                'systemInfo' => [
                    'memory' => 'N/A',
                    'cpu' => 'N/A',
                    'disk' => 'N/A',
                    'queue_size' => 0
                ],
                'recentLogs' => []
            ]);
        }
    }

    private function getBasicWorkerStatus()
    {
        Log::info('WorkerMonitorController: Recupero stato worker');
        
        return [
            [
                'name' => 'Worker 1',
                'state' => 'RUNNING',
                'pid' => 'N/A',
                'uptime' => 'N/A'
            ],
            [
                'name' => 'Worker 2',
                'state' => 'RUNNING',
                'pid' => 'N/A',
                'uptime' => 'N/A'
            ]
        ];
    }

    private function getBasicSystemInfo()
    {
        Log::info('WorkerMonitorController: Recupero info sistema');
        
        return [
            'memory' => 'Memoria: OK',
            'cpu' => 'CPU: OK',
            'disk' => 'Disco: OK',
            'queue_size' => 0
        ];
    }

    private function getBasicLogs()
    {
        Log::info('WorkerMonitorController: Recupero log');
        
        return [
            '[' . now() . '] Worker attivo',
            '[' . now() . '] Sistema operativo'
        ];
    }

    private function getWorkerStatus()
    {
        $status = [];
        
        try {
            // Usa il file di log per determinare lo stato
            $logPath = storage_path('logs/worker.log');
            if (file_exists($logPath)) {
                $logs = array_slice(file($logPath), -50);
                foreach ($logs as $log) {
                    if (strpos($log, 'Processing') !== false) {
                        $status[] = [
                            'name' => 'Worker',
                            'state' => 'RUNNING',
                            'pid' => 'N/A',
                            'uptime' => 'N/A'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Errore nel recupero stato worker: ' . $e->getMessage());
        }
        
        return $status;
    }

    private function getSystemInfo()
    {
        return [
            'memory' => $this->getMemoryUsage(),
            'cpu' => $this->getCpuUsage(),
            'disk' => $this->getDiskUsage(),
            'queue_size' => $this->getQueueSize()
        ];
    }

    private function getMemoryUsage()
    {
        try {
            // Usa le funzioni native di PHP per la memoria
            $total = memory_get_usage(true);
            $free = memory_get_peak_usage(true);
            $used = $total - $free;
            
            return sprintf(
                "Memoria Totale: %s\nMemoria Usata: %s\nMemoria Libera: %s",
                $this->formatBytes($total),
                $this->formatBytes($used),
                $this->formatBytes($free)
            );
        } catch (\Exception $e) {
            Log::error('Errore nel recupero memoria: ' . $e->getMessage());
            return "Informazioni memoria non disponibili";
        }
    }

    private function getCpuUsage()
    {
        try {
            // Usa le funzioni native di PHP per il carico
            $load = sys_getloadavg();
            return sprintf(
                "Load Average: %.2f, %.2f, %.2f",
                $load[0],
                $load[1],
                $load[2]
            );
        } catch (\Exception $e) {
            Log::error('Errore nel recupero CPU: ' . $e->getMessage());
            return "Informazioni CPU non disponibili";
        }
    }

    private function getDiskUsage()
    {
        try {
            // Usa le funzioni native di PHP per il disco
            $total = disk_total_space(base_path());
            $free = disk_free_space(base_path());
            $used = $total - $free;
            
            return sprintf(
                "Spazio Totale: %s\nSpazio Usato: %s\nSpazio Libero: %s",
                $this->formatBytes($total),
                $this->formatBytes($used),
                $this->formatBytes($free)
            );
        } catch (\Exception $e) {
            Log::error('Errore nel recupero disco: ' . $e->getMessage());
            return "Informazioni disco non disponibili";
        }
    }

    private function getQueueSize()
    {
        try {
            // Prima prova con Queue::size()
            $size = Queue::size('default');
            
            // Se non funziona, prova a contare direttamente dalla tabella
            if ($size === null) {
                $size = DB::table('jobs')->count();
            }
            
            Log::info('Queue size retrieved', ['size' => $size]);
            return $size;
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dimensione coda: ' . $e->getMessage());
            return 0;
        }
    }

    private function getRecentLogs()
    {
        try {
            $logPath = storage_path('logs/worker.log');
            if (file_exists($logPath)) {
                $logs = array_slice(file($logPath), -50);
                return array_reverse($logs);
            }
        } catch (\Exception $e) {
            Log::error('Errore nel recupero log: ' . $e->getMessage());
        }
        return [];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 