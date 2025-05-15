<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class QueueMonitor extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitora lo stato dei worker Laravel';

    public function handle()
    {
        // Usa il file di log per determinare lo stato
        $logPath = storage_path('logs/worker.log');
        if (file_exists($logPath)) {
            $logs = array_slice(file($logPath), -50);
            foreach ($logs as $log) {
                if (strpos($log, 'Processing') !== false || 
                    strpos($log, 'Processed') !== false ||
                    strpos($log, 'Failed') !== false) {
                    $this->line($log);
                }
            }
        }

        // Mostra informazioni sulla coda
        $this->info('Queue Size: ' . $this->getQueueSize());
        
        // Mostra informazioni sul sistema
        $this->info('Memory Usage: ' . $this->getMemoryUsage());
        $this->info('CPU Load: ' . $this->getCpuLoad());
        $this->info('Disk Usage: ' . $this->getDiskUsage());
    }

    private function getQueueSize()
    {
        return Cache::remember('queue_size', 60, function () {
            return $this->call('queue:size');
        });
    }

    private function getMemoryUsage()
    {
        $total = memory_get_usage(true);
        $free = memory_get_peak_usage(true);
        $used = $total - $free;
        
        return sprintf(
            "Total: %s, Used: %s, Free: %s",
            $this->formatBytes($total),
            $this->formatBytes($used),
            $this->formatBytes($free)
        );
    }

    private function getCpuLoad()
    {
        $load = sys_getloadavg();
        return sprintf("%.2f, %.2f, %.2f", $load[0], $load[1], $load[2]);
    }

    private function getDiskUsage()
    {
        $total = disk_total_space(base_path());
        $free = disk_free_space(base_path());
        $used = $total - $free;
        
        return sprintf(
            "Total: %s, Used: %s, Free: %s",
            $this->formatBytes($total),
            $this->formatBytes($used),
            $this->formatBytes($free)
        );
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