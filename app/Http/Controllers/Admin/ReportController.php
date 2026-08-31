<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    public function index(): View
    {
        return view('admin.reports.index', ['reportTypes' => ReportService::REPORTS]);
    }

    public function show(Request $request, string $type): View
    {
        $filters = $this->extractFilters($request);
        $report = $this->reports->generate($type, $filters);

        return view('admin.reports.show', [
            'type' => $type,
            'report' => $report,
            'filters' => $filters,
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'reportTypes' => ReportService::REPORTS,
        ]);
    }

    public function exportCsv(Request $request, string $type): StreamedResponse
    {
        $report = $this->reports->generate($type, $this->extractFilters($request));

        $filename = str($report['title'])->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $report['headers']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, string $type): Response
    {
        $report = $this->reports->generate($type, $this->extractFilters($request));

        $pdf = Pdf::loadView('pdf.report', ['report' => $report, 'filters' => $this->extractFilters($request)])
            ->setPaper('a4', 'landscape');

        $filename = str($report['title'])->slug().'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    protected function extractFilters(Request $request): array
    {
        return array_filter([
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'property_id' => $request->input('property_id'),
            'property_type' => $request->input('property_type'),
        ]);
    }
}
