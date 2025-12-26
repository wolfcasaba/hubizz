<?php

namespace App\Jobs;

use App\Services\Affiliate\RevenueTrackerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Revenue Report Job
 *
 * Background job for generating comprehensive revenue reports.
 */
class GenerateRevenueReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    protected ?\DateTime $startDate;
    protected ?\DateTime $endDate;
    protected string $format;
    protected string $reportType;

    /**
     * Create a new job instance.
     *
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param string $format csv|json|pdf
     * @param string $reportType summary|detailed|network
     */
    public function __construct(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        string $format = 'csv',
        string $reportType = 'summary'
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->format = $format;
        $this->reportType = $reportType;
    }

    /**
     * Execute the job.
     *
     * @param RevenueTrackerService $revenueTracker
     * @return void
     */
    public function handle(RevenueTrackerService $revenueTracker): void
    {
        Log::info('Generating revenue report', [
            'start_date' => $this->startDate?->format('Y-m-d'),
            'end_date' => $this->endDate?->format('Y-m-d'),
            'format' => $this->format,
            'type' => $this->reportType,
        ]);

        try {
            $reportData = $this->generateReportData($revenueTracker);

            // Generate report file
            $filename = $this->generateFilename();
            $content = $this->formatReportContent($reportData);

            // Save to storage
            Storage::disk('local')->put('reports/' . $filename, $content);

            Log::info('Revenue report generated successfully', [
                'filename' => $filename,
                'size' => strlen($content),
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate report data based on type.
     *
     * @param RevenueTrackerService $revenueTracker
     * @return array
     */
    protected function generateReportData(RevenueTrackerService $revenueTracker): array
    {
        return match ($this->reportType) {
            'summary' => [
                'statistics' => $revenueTracker->getRevenueStatistics($this->startDate, $this->endDate),
                'trend' => $revenueTracker->getRevenueTrend('daily', 30),
            ],
            'network' => [
                'by_network' => $revenueTracker->getRevenueByNetwork($this->startDate, $this->endDate),
                'statistics' => $revenueTracker->getRevenueStatistics($this->startDate, $this->endDate),
            ],
            'detailed' => [
                'statistics' => $revenueTracker->getRevenueStatistics($this->startDate, $this->endDate),
                'by_network' => $revenueTracker->getRevenueByNetwork($this->startDate, $this->endDate),
                'click_analytics' => $revenueTracker->getClickAnalytics($this->startDate, $this->endDate),
                'trend' => $revenueTracker->getRevenueTrend('daily', 30),
            ],
            default => [],
        };
    }

    /**
     * Format report content based on format.
     *
     * @param array $data
     * @return string
     */
    protected function formatReportContent(array $data): string
    {
        return match ($this->format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->formatAsCsv($data),
            default => json_encode($data, JSON_PRETTY_PRINT),
        };
    }

    /**
     * Format data as CSV.
     *
     * @param array $data
     * @return string
     */
    protected function formatAsCsv(array $data): string
    {
        $csv = "Hubizz Revenue Report\n";
        $csv .= "Generated: " . now()->toDateTimeString() . "\n";
        $csv .= "Period: " . ($this->startDate?->format('Y-m-d') ?? 'All time') . " to " . ($this->endDate?->format('Y-m-d') ?? 'Now') . "\n\n";

        if (isset($data['statistics'])) {
            $stats = $data['statistics'];
            $csv .= "Summary Statistics\n";
            $csv .= "Total Clicks," . $stats['total_clicks'] . "\n";
            $csv .= "Total Conversions," . $stats['total_conversions'] . "\n";
            $csv .= "Total Revenue,$" . $stats['total_revenue'] . "\n";
            $csv .= "Conversion Rate," . $stats['conversion_rate'] . "%\n";
            $csv .= "Avg Revenue Per Click,$" . $stats['avg_revenue_per_click'] . "\n\n";
        }

        if (isset($data['by_network'])) {
            $csv .= "Revenue by Network\n";
            $csv .= "Network,Links,Clicks,Conversions,Revenue,Conversion Rate\n";
            foreach ($data['by_network'] as $network) {
                $csv .= sprintf(
                    '"%s",%d,%d,%d,$%.2f,%.2f%%' . "\n",
                    $network['network_name'],
                    $network['total_links'],
                    $network['total_clicks'],
                    $network['total_conversions'],
                    $network['total_revenue'],
                    $network['conversion_rate']
                );
            }
        }

        return $csv;
    }

    /**
     * Generate filename for report.
     *
     * @return string
     */
    protected function generateFilename(): string
    {
        $date = now()->format('Y-m-d_His');
        $period = '';

        if ($this->startDate) {
            $period .= '_' . $this->startDate->format('Ymd');
        }

        if ($this->endDate) {
            $period .= '_to_' . $this->endDate->format('Ymd');
        }

        return "revenue_report_{$this->reportType}{$period}_{$date}.{$this->format}";
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Revenue report generation job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['affiliate', 'report', 'revenue'];
    }
}
